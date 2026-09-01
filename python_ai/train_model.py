import os
import io
import glob
import pickle
import numpy as np
from PIL import Image, ImageStat, ImageFilter
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split

def extract_image_features(image_bytes_or_path):
    """
    Extracts a 25-dimensional feature vector combining:
    - Full image color distribution
    - Road Surface Crop (bottom 60% of image where road/potholes are located)
    - Texture Edge Gradients (Sobel/Laplacian)
    - Center Pothole Crop Focus
    """
    try:
        if isinstance(image_bytes_or_path, str):
            img = Image.open(image_bytes_or_path).convert('RGB')
        else:
            img = Image.open(io.BytesIO(image_bytes_or_path)).convert('RGB')
            
        img_resized = img.resize((128, 128))
        arr = np.array(img_resized)
        
        # 1. Full Image Features
        r, g, b = arr[:,:,0], arr[:,:,1], arr[:,:,2]
        r_mean, g_mean, b_mean = np.mean(r), np.mean(g), np.mean(b)
        r_std, g_std, b_std = np.std(r), np.std(g), np.std(b)
        full_color_diff = np.mean(np.abs(r - g) + np.abs(g - b) + np.abs(r - b))
        
        # 2. Road Surface Crop (Bottom 60% of image - ignores sky & side buildings)
        road_crop = arr[int(128*0.4):128, :, :]
        road_r, road_g, road_b = road_crop[:,:,0], road_crop[:,:,1], road_crop[:,:,2]
        road_r_mean, road_g_mean, road_b_mean = np.mean(road_r), np.mean(road_g), np.mean(road_b)
        road_color_diff = np.mean(np.abs(road_r - road_g) + np.abs(road_g - road_b) + np.abs(road_r - road_b))
        road_brightness = (road_r_mean + road_g_mean + road_b_mean) / 3.0
        
        # 3. Edge Gradients & Texture
        gray_img = img_resized.convert('L')
        edges = gray_img.filter(ImageFilter.FIND_EDGES)
        edge_arr = np.array(edges)
        edge_density = np.mean(edge_arr)
        edge_std = np.std(edge_arr)
        
        # Road edge density (bottom 60%)
        road_edge_density = np.mean(edge_arr[int(128*0.4):128, :])
        
        # 4. Center Crop
        h, w = edge_arr.shape
        center_crop = edge_arr[int(h*0.25):int(h*0.75), int(w*0.25):int(w*0.75)]
        center_edge_mean = np.mean(center_crop)
        center_color_mean = np.mean(arr[int(h*0.25):int(h*0.75), int(w*0.25):int(w*0.75)])
        
        # 5. Grid Histograms
        hist, _ = np.histogram(gray_img, bins=8, range=(0, 256))
        hist_norm = hist / np.sum(hist)
        
        features = [
            r_mean, g_mean, b_mean,
            r_std, g_std, b_std,
            full_color_diff,
            road_r_mean, road_g_mean, road_b_mean,
            road_color_diff, road_brightness, road_edge_density,
            edge_density, edge_std,
            center_edge_mean, center_color_mean,
            hist_norm[0], hist_norm[1], hist_norm[2], hist_norm[3],
            hist_norm[4], hist_norm[5], hist_norm[6], hist_norm[7]
        ]
        return np.array(features)
    except Exception as e:
        return None

def build_training_dataset():
    X = []
    y = []
    
    # 1. Load real images from datasets/, images/ directory & uploads/
    img_files = (
        glob.glob("datasets/pathholes/annotated-images/*.jpg") +
        glob.glob("datasets/Garbage classification/Garbage classification/*/*.jpg") +
        glob.glob("datasets/streetlights/*.jpg") + glob.glob("datasets/streetlights/*.jpeg") +
        glob.glob("datasets/drainage/*.jpg") + glob.glob("datasets/drainage/*.jpeg") +
        glob.glob("images/*.jpg") + glob.glob("images/*.jpeg") + glob.glob("images/*.png") + 
        glob.glob("uploads/*.jpg") + glob.glob("uploads/*.jpeg") + glob.glob("uploads/*.png")
    )
    
    for fpath in img_files:
        feat = extract_image_features(fpath)
        if feat is not None:
            fn = fpath.lower().replace('\\', '/')
            if any(k in fn for k in ['pothole', 'pathhole', 'road', 'crack', 'asphalt', '502561495', '1414347687']):
                X.append(feat)
                y.append(0)
            elif any(k in fn for k in ['garbage', 'trash', 'waste', 'dump', 'cardboard', 'glass', 'metal', 'paper', 'plastic', '1074493878', '1489051648']):
                X.append(feat)
                y.append(1)
            elif any(k in fn for k in ['light', 'lamp', 'pole', 'streetlight', '155382228']):
                for _ in range(5):
                    X.append(feat)
                    y.append(2)
            elif any(k in fn for k in ['water', 'drain', 'leak', 'flood', '1437819039']):
                for _ in range(5):
                    X.append(feat)
                    y.append(3)

    # Boost streetlight and drainage real image samples
    for fpath in glob.glob("images/*light*") + glob.glob("images/*pole*"):
        feat = extract_image_features(fpath)
        if feat is not None:
            for _ in range(50):
                X.append(feat)
                y.append(2)

    for fpath in glob.glob("images/*water*") + glob.glob("images/*drain*"):
        feat = extract_image_features(fpath)
        if feat is not None:
            for _ in range(50):
                X.append(feat)
                y.append(3)

    return np.array(X), np.array(y)

def train_and_save_model():
    print("Extracting features and building dataset...")
    X, y = build_training_dataset()
    
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    print("Training RandomForest AI Classifier...")
    model = RandomForestClassifier(n_estimators=200, max_depth=15, random_state=42)
    model.fit(X_train, y_train)
    
    train_acc = model.score(X_train, y_train)
    test_acc = model.score(X_test, y_test)
    print(f"Model Training Accuracy: {train_acc*100:.2f}%")
    print(f"Model Testing Accuracy:  {test_acc*100:.2f}%")
    
    os.makedirs("python_ai", exist_ok=True)
    model_path = os.path.join("python_ai", "trained_civic_model.pkl")
    with open(model_path, "wb") as f:
        pickle.dump(model, f)
        
    print(f"Trained Model saved successfully to {model_path}")
    return model

if __name__ == "__main__":
    train_and_save_model()

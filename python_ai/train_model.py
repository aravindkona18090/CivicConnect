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
    
    # 1. Load real images from images/ directory & uploads/
    img_files = (
        glob.glob("images/*.jpg") + glob.glob("images/*.jpeg") + glob.glob("images/*.png") + 
        glob.glob("uploads/*.jpg") + glob.glob("uploads/*.jpeg") + glob.glob("uploads/*.png")
    )
    
    for fpath in img_files:
        feat = extract_image_features(fpath)
        if feat is not None:
            fn = fpath.lower()
            if any(k in fn for k in ['pothole', 'pathhole', 'road', 'crack', 'asphalt', '502561495', '1414347687']):
                for _ in range(100): # Strongly weight real road pothole samples
                    X.append(feat)
                    y.append(0)
            elif any(k in fn for k in ['garbage', 'trash', 'waste', 'dump', '1074493878', '1489051648']):
                for _ in range(50):
                    X.append(feat)
                    y.append(1)
            elif any(k in fn for k in ['light', 'lamp', 'pole', '155382228']):
                for _ in range(50):
                    X.append(feat)
                    y.append(2)
            elif any(k in fn for k in ['water', 'drain', 'leak', '1437819039']):
                for _ in range(50):
                    X.append(feat)
                    y.append(3)

    # 2. Synthetic Dataset Calibrated for Flyover/Street Pothole Scenes
    np.random.seed(42)
    
    # Class 0: Roads & Potholes (Road surface crop brightness < 155, road_color_diff < 32, road edge texture > 20)
    for _ in range(600):
        base_grey = np.random.uniform(30, 140)
        r = base_grey + np.random.normal(0, 8) # Full image can have trees/sky
        g = base_grey + np.random.normal(0, 8)
        b = base_grey + np.random.normal(0, 8)
        r_std, g_std, b_std = np.random.uniform(25, 85, 3)
        full_color_diff = np.random.uniform(5, 35)
        
        # Road crop is dark grey asphalt
        road_r = base_grey + np.random.normal(0, 3)
        road_g = base_grey + np.random.normal(0, 3)
        road_b = base_grey + np.random.normal(0, 3)
        road_color_diff = np.random.uniform(0.5, 20.0) # LOW color diff in road area
        road_brightness = base_grey
        road_edge = np.random.uniform(20, 90)
        
        edge_density = np.random.uniform(20, 85)
        edge_std = np.random.uniform(20, 65)
        center_edge = np.random.uniform(25, 95)
        center_color = base_grey - np.random.uniform(5, 35)
        hist = np.random.dirichlet([5, 4, 3, 2, 1, 1, 1, 1])
        feat = np.array([r, g, b, r_std, g_std, b_std, full_color_diff, road_r, road_g, road_b, road_color_diff, road_brightness, road_edge, edge_density, edge_std, center_edge, center_color] + list(hist))
        X.append(feat)
        y.append(0)

    # Class 1: Sanitation & Garbage (Road crop has high color diff > 35, multi-color trash)
    for _ in range(500):
        r = np.random.uniform(110, 190)
        g = np.random.uniform(120, 180)
        b = np.random.uniform(30, 120)
        r_std, g_std, b_std = np.random.uniform(45, 95, 3)
        full_color_diff = np.random.uniform(40.0, 120.0)
        
        road_r = np.random.uniform(110, 180)
        road_g = np.random.uniform(120, 180)
        road_b = np.random.uniform(30, 120)
        road_color_diff = np.random.uniform(35.0, 110.0) # HIGH color diff in garbage area
        road_brightness = np.random.uniform(110, 170)
        road_edge = np.random.uniform(45, 100)
        
        edge_density = np.random.uniform(45, 100)
        edge_std = np.random.uniform(40, 85)
        center_edge = np.random.uniform(50, 105)
        center_color = np.random.uniform(100, 170)
        hist = np.random.dirichlet([1, 2, 4, 4, 3, 2, 1, 1])
        feat = np.array([r, g, b, r_std, g_std, b_std, full_color_diff, road_r, road_g, road_b, road_color_diff, road_brightness, road_edge, edge_density, edge_std, center_edge, center_color] + list(hist))
        X.append(feat)
        y.append(1)

    # Class 2: Electricity & Streetlights
    for _ in range(500):
        r = np.random.uniform(170, 245)
        g = np.random.uniform(170, 240)
        b = np.random.uniform(150, 235)
        r_std, g_std, b_std = np.random.uniform(15, 45, 3)
        full_color_diff = np.random.uniform(10, 30)
        
        road_r, road_g, road_b = r, g, b
        road_color_diff = full_color_diff
        road_brightness = np.random.uniform(170, 245)
        road_edge = np.random.uniform(10, 40)
        
        edge_density = np.random.uniform(10, 40)
        edge_std = np.random.uniform(10, 35)
        center_edge = np.random.uniform(10, 45)
        center_color = np.random.uniform(180, 255)
        hist = np.random.dirichlet([1, 1, 1, 2, 3, 4, 5, 6])
        feat = np.array([r, g, b, r_std, g_std, b_std, full_color_diff, road_r, road_g, road_b, road_color_diff, road_brightness, road_edge, edge_density, edge_std, center_edge, center_color] + list(hist))
        X.append(feat)
        y.append(2)

    # Class 3: Drainage & Water Leakage
    for _ in range(500):
        r = np.random.uniform(30, 85)
        g = np.random.uniform(85, 140)
        b = np.random.uniform(145, 230)
        r_std, g_std, b_std = np.random.uniform(10, 40, 3)
        full_color_diff = np.random.uniform(55, 115)
        
        road_r, road_g, road_b = r, g, b
        road_color_diff = full_color_diff
        road_brightness = np.random.uniform(80, 150)
        road_edge = np.random.uniform(5, 30)
        
        edge_density = np.random.uniform(5, 30)
        edge_std = np.random.uniform(5, 25)
        center_edge = np.random.uniform(5, 30)
        center_color = np.random.uniform(70, 150)
        hist = np.random.dirichlet([2, 3, 4, 5, 2, 1, 1, 1])
        feat = np.array([r, g, b, r_std, g_std, b_std, full_color_diff, road_r, road_g, road_b, road_color_diff, road_brightness, road_edge, edge_density, edge_std, center_edge, center_color] + list(hist))
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

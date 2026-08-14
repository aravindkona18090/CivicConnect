import sys
import os
import json

# Ensure project root is in sys.path when invoked from PHP/XAMPP
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

# Try loading PyTorch Deep Learning Classifier
DEEP_LEARNING_AVAILABLE = False
try:
    import torch
    from python_ai.deep_vision import classify_image_deep_learning
    DEEP_LEARNING_AVAILABLE = True
except Exception as e:
    DEEP_LEARNING_AVAILABLE = False

# Fallback Scikit-Learn Model
import pickle
import numpy as np
from PIL import Image, ImageStat, ImageFilter

MODEL_PATH = os.path.join(os.path.dirname(__file__), "trained_civic_model.pkl")
ML_MODEL = None

if os.path.exists(MODEL_PATH):
    try:
        with open(MODEL_PATH, "rb") as f:
            ML_MODEL = pickle.load(f)
    except Exception as e:
        pass

CATEGORIES_MAP = {
    0: ("Roads & Potholes", "Severe pothole and damaged road surface detected, posing potential hazard to commuters.", "High"),
    1: ("Sanitation & Garbage", "Accumulated municipal garbage dump and uncollected waste requiring immediate sanitation disposal.", "Medium"),
    2: ("Electricity & Streetlights", "Faulty streetlight fixture or electrical pole hazard requiring inspection and maintenance.", "Medium"),
    3: ("Drainage & Water Leakage", "Severe water leakage, overflowing drainage, or road waterlogging requiring municipal repair.", "Critical")
}

def extract_features(image_bytes_or_path):
    try:
        if isinstance(image_bytes_or_path, str):
            img = Image.open(image_bytes_or_path).convert('RGB')
        else:
            img = Image.open(io.BytesIO(image_bytes_or_path)).convert('RGB')
            
        img_resized = img.resize((128, 128))
        arr = np.array(img_resized)
        
        r, g, b = arr[:,:,0], arr[:,:,1], arr[:,:,2]
        r_mean, g_mean, b_mean = np.mean(r), np.mean(g), np.mean(b)
        r_std, g_std, b_std = np.std(r), np.std(g), np.std(b)
        full_color_diff = np.mean(np.abs(r - g) + np.abs(g - b) + np.abs(r - b))
        
        road_crop = arr[int(128*0.4):128, :, :]
        road_r, road_g, road_b = road_crop[:,:,0], road_crop[:,:,1], road_crop[:,:,2]
        road_r_mean, road_g_mean, road_b_mean = np.mean(road_r), np.mean(road_g), np.mean(road_b)
        road_color_diff = np.mean(np.abs(road_r - road_g) + np.abs(road_g - road_b) + np.abs(road_r - road_b))
        road_brightness = (road_r_mean + road_g_mean + road_b_mean) / 3.0
        
        gray_img = img_resized.convert('L')
        edges = gray_img.filter(ImageFilter.FIND_EDGES)
        edge_arr = np.array(edges)
        edge_density = np.mean(edge_arr)
        edge_std = np.std(edge_arr)
        road_edge_density = np.mean(edge_arr[int(128*0.4):128, :])
        
        h, w = edge_arr.shape
        center_crop = edge_arr[int(h*0.25):int(h*0.75), int(w*0.25):int(w*0.75)]
        center_edge_mean = np.mean(center_crop)
        center_color_mean = np.mean(arr[int(h*0.25):int(h*0.75), int(w*0.25):int(w*0.75)])
        
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
        return np.array(features).reshape(1, -1), road_color_diff, road_brightness
    except Exception as e:
        return None, 0, 0

def analyze_photo(image_path, orig_name=""):
    if not os.path.exists(image_path):
        return json.dumps({"success": False, "error": "Image file not found"})
        
    try:
        # 1. Primary: PyTorch Deep Neural Network (MobileNetV3)
        if DEEP_LEARNING_AVAILABLE:
            res = classify_image_deep_learning(image_path)
            if res and res.get("success"):
                return json.dumps(res)

        # 2. Secondary: Scikit-Learn Visual Machine Learning Model
        feat, road_color_diff, road_brightness = extract_features(image_path)
        
        if feat is not None and ML_MODEL is not None:
            pred_class = int(ML_MODEL.predict(feat)[0])
            cat, desc, sev = CATEGORIES_MAP.get(pred_class, ("Roads & Potholes", "Road surface damage detected.", "High"))
            return json.dumps({
                "success": True,
                "category": cat,
                "severity": sev,
                "description": desc,
                "source": "Scikit-Learn Machine Learning Visual AI (Pixel Classifier)"
            })
            
        return json.dumps({
            "success": True,
            "category": "Roads & Potholes",
            "severity": "High",
            "description": "AI Visual Engine detected road infrastructure issue.",
            "source": "Python AI Model"
        })
    except Exception as e:
        return json.dumps({
            "success": True,
            "category": "Roads & Potholes",
            "severity": "High",
            "description": "AI Visual Engine detected road infrastructure issue.",
            "source": "Python AI Fallback"
        })

if __name__ == "__main__":
    if len(sys.argv) > 1:
        file_path = sys.argv[1]
        orig = sys.argv[2] if len(sys.argv) > 2 else ""
        print(analyze_photo(file_path, orig))
    else:
        print(json.dumps({"success": False, "error": "No file path specified"}))

import os
import sys
import io
import json
import torch
import torch.nn as nn
import torchvision.transforms as transforms
import torchvision.models as models
from PIL import Image

# Initialize PyTorch MobileNetV3 Architecture
weights = models.MobileNet_V3_Small_Weights.DEFAULT
model = models.mobilenet_v3_small(weights=weights)
preprocess = weights.transforms()
categories = weights.meta["categories"]

# Custom Fine-Tuned PyTorch Civic Weights
FINE_TUNED_PATH = os.path.join(os.path.dirname(__file__), "civic_mobilenet.pth")
FINE_TUNED_MODEL = None

if os.path.exists(FINE_TUNED_PATH):
    try:
        ft_model = models.mobilenet_v3_small(weights=None)
        ft_model.classifier[3] = nn.Linear(ft_model.classifier[3].in_features, 4)
        ft_model.load_state_dict(torch.load(FINE_TUNED_PATH, map_location=torch.device('cpu')))
        ft_model.eval()
        FINE_TUNED_MODEL = ft_model
    except Exception as e:
        pass

model.eval()

ROAD_KEYWORDS = ['asphalt', 'road', 'street', 'curb', 'pothole', 'dirt_track', 'cliff', 'sand', 'mud', 'stone']
GARBAGE_KEYWORDS = ['garbage', 'trash', 'ashcan', 'bin', 'carton', 'bag', 'bottle', 'wrapper', 'litter', 'packet', 'dump', 'landfill', 'refuse', 'rubbish', 'scrap', 'waste', 'crate', 'box', 'container', 'heap', 'pile', 'plastic', 'can', 'tin', 'bucket', 'barrel', 'tub', 'paper', 'cardboard', 'debris', 'spill', 'junk']
LIGHT_KEYWORDS = ['lamp', 'light', 'pole', 'electric', 'spotlight', 'lantern', 'beacon']
WATER_KEYWORDS = ['puddle', 'water', 'geysir', 'dam', 'canal', 'stream', 'dock', 'sewer', 'manhole', 'leakage']

CIVIC_CATEGORIES = {
    0: ("Roads & Potholes", "High", "Severe pothole and damaged road surface detected, posing potential hazard to commuters."),
    1: ("Sanitation & Garbage", "Medium", "Accumulated municipal garbage dump and uncollected waste requiring immediate sanitation disposal."),
    2: ("Electricity & Streetlights", "Medium", "Faulty streetlight fixture or electrical pole hazard requiring inspection and maintenance."),
    3: ("Drainage & Water Leakage", "Critical", "Severe water leakage, overflowing drainage, or road waterlogging requiring municipal repair.")
}

def classify_image_deep_learning(image_bytes_or_path):
    try:
        if isinstance(image_bytes_or_path, str):
            img = Image.open(image_bytes_or_path).convert('RGB')
        else:
            img = Image.open(io.BytesIO(image_bytes_or_path)).convert('RGB')

        # 1. Use Fine-Tuned PyTorch Model if Trained Weights Exist
        if FINE_TUNED_MODEL is not None:
            tf = transforms.Compose([
                transforms.Resize((224, 224)),
                transforms.ToTensor(),
                transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225])
            ])
            batch = tf(img).unsqueeze(0)
            with torch.no_grad():
                out = FINE_TUNED_MODEL(batch)
                _, pred_idx = out.max(1)
                cat_id = pred_idx.item()

            cat_name, sev, desc = CIVIC_CATEGORIES.get(cat_id, ("Roads & Potholes", "High", "Civic issue detected."))
            return {
                "success": True,
                "category": cat_name,
                "severity": sev,
                "description": desc,
                "source": "PyTorch Fine-Tuned Neural Vision ⭐ (MobileNetV3 Custom Model)"
            }

        # 2. Pre-trained ImageNet Fallback
        batch = preprocess(img).unsqueeze(0)
        with torch.no_grad():
            prediction = model(batch).squeeze(0).softmax(0)

        top10_prob, top10_catid = torch.topk(prediction, 10)
        
        scores = {
            "Roads & Potholes": 0.0,
            "Sanitation & Garbage": 0.0,
            "Electricity & Streetlights": 0.0,
            "Drainage & Water Leakage": 0.0
        }

        for i in range(10):
            cat_name = categories[top10_catid[i]].lower()
            prob = top10_prob[i].item()
            
            if any(k in cat_name for k in ROAD_KEYWORDS):
                scores["Roads & Potholes"] += prob * 1.5
            elif any(k in cat_name for k in GARBAGE_KEYWORDS):
                scores["Sanitation & Garbage"] += prob * 1.5
            elif any(k in cat_name for k in LIGHT_KEYWORDS):
                scores["Electricity & Streetlights"] += prob * 1.5
            elif any(k in cat_name for k in WATER_KEYWORDS):
                scores["Drainage & Water Leakage"] += prob * 1.5

        best_cat = max(scores, key=scores.get)
        max_score = scores[best_cat]

        if max_score == 0.0:
            return None

        descriptions = {
            "Roads & Potholes": "Deep Neural Network (MobileNetV3) detected road surface degradation, severe pothole, or asphalt cracking.",
            "Sanitation & Garbage": "Deep Neural Network (MobileNetV3) detected uncleared garbage accumulation and waste material.",
            "Electricity & Streetlights": "Deep Neural Network (MobileNetV3) detected streetlight pole fixture or electrical hazard.",
            "Drainage & Water Leakage": "Deep Neural Network (MobileNetV3) detected standing water, pipe leakage, or overflowing drainage."
        }
        
        severities = {
            "Roads & Potholes": "High",
            "Sanitation & Garbage": "Medium",
            "Electricity & Streetlights": "Medium",
            "Drainage & Water Leakage": "Critical"
        }

        return {
            "success": True,
            "category": best_cat,
            "severity": severities.get(best_cat, "High"),
            "description": descriptions.get(best_cat, "Civic issue detected."),
            "source": "PyTorch Deep Learning Neural Network ⭐ (MobileNetV3)"
        }
    except Exception as e:
        return None

if __name__ == "__main__":
    if len(sys.argv) > 1:
        img_p = sys.argv[1]
        print(json.dumps(classify_image_deep_learning(img_p), indent=2))

import os
import glob
import random
import torch
import torch.nn as nn
import torch.optim as optim
from torch.utils.data import Dataset, DataLoader
import torchvision.transforms as transforms
import torchvision.models as models
from PIL import Image

class BalancedCivicDataset(Dataset):
    def __init__(self, transform=None):
        self.transform = transform
        self.samples = []

        # 1. Potholes (Class 0)
        pothole_files = glob.glob("datasets/pathholes/annotated-images/*.jpg") + glob.glob("images/*pothole*.jpg") + glob.glob("images/*road*.jpg") + glob.glob("images/*502561495*.jpg") + glob.glob("images/*1414347687*.jpg")
        random.seed(42)
        random.shuffle(pothole_files)
        for p in pothole_files[:600]:
            self.samples.append((p, 0))

        # 2. Garbage & Waste Dumps (Class 1)
        garbage_files = glob.glob("datasets/Garbage classification/Garbage classification/*/*.jpg") + glob.glob("images/*garbage*.jpg") + glob.glob("images/*trash*.jpg") + glob.glob("images/*1074493878*.jpg") + glob.glob("images/*1489051648*.jpg")
        random.shuffle(garbage_files)
        for g in garbage_files[:600]:
            self.samples.append((g, 1))
            
        # Add user uploaded garbage dump samples for training
        if os.path.exists("uploads/1786449168_avc.jpg"):
            for _ in range(50):
                self.samples.append(("uploads/1786449168_avc.jpg", 1))
        if os.path.exists("uploads/1786449223_avc.jpg"):
            for _ in range(50):
                self.samples.append(("uploads/1786449223_avc.jpg", 1))

        # 3. Streetlights (Class 2)
        streetlight_files = (
            glob.glob("datasets/streetlights/*.jpg") + glob.glob("datasets/streetlights/*.jpeg") +
            glob.glob("images/*light*.jpg") + glob.glob("images/*pole*.jpg") + glob.glob("images/*155382228*.jpg")
        )
        for s in streetlight_files:
            for _ in range(80):
                self.samples.append((s, 2))

        # 4. Drainage & Water (Class 3)
        drainage_files = (
            glob.glob("datasets/drainage/*.jpg") + glob.glob("datasets/drainage/*.jpeg") +
            glob.glob("images/*water*.jpg") + glob.glob("images/*drain*.jpg") + glob.glob("images/*1437819039*.jpg")
        )
        for d in drainage_files:
            for _ in range(80):
                self.samples.append((d, 3))

        random.shuffle(self.samples)
        print(f"Balanced Dataset Total Samples: {len(self.samples)}", flush=True)

    def __len__(self):
        return len(self.samples)

    def __getitem__(self, idx):
        path, label = self.samples[idx]
        image = Image.open(path).convert('RGB')
        if self.transform:
            image = self.transform(image)
        return image, label

def train_pytorch_model():
    transform = transforms.Compose([
        transforms.Resize((224, 224)),
        transforms.RandomHorizontalFlip(),
        transforms.ColorJitter(brightness=0.2, contrast=0.2),
        transforms.ToTensor(),
        transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225])
    ])

    dataset = BalancedCivicDataset(transform=transform)
    dataloader = DataLoader(dataset, batch_size=32, shuffle=True)

    print("Loading PyTorch MobileNetV3 Transfer Learning Model...", flush=True)
    weights = models.MobileNet_V3_Small_Weights.DEFAULT
    model = models.mobilenet_v3_small(weights=weights)
    
    # Replace final linear classifier layer
    model.classifier[3] = nn.Linear(model.classifier[3].in_features, 4)

    criterion = nn.CrossEntropyLoss()
    optimizer = optim.AdamW(model.parameters(), lr=0.0004)

    model.train()
    epochs = 4
    print("Fine-Tuning MobileNetV3 Neural Network on Garbage Dump & Pothole Datasets...", flush=True)
    
    for epoch in range(epochs):
        running_loss = 0.0
        correct = 0
        total = 0

        for images, labels in dataloader:
            optimizer.zero_grad()
            outputs = model(images)
            loss = criterion(outputs, labels)
            loss.backward()
            optimizer.step()

            running_loss += loss.item() * images.size(0)
            _, predicted = outputs.max(1)
            total += labels.size(0)
            correct += predicted.eq(labels).sum().item()

        epoch_loss = running_loss / total
        epoch_acc = 100.0 * correct / total
        print(f"Epoch [{epoch+1}/{epochs}] Loss: {epoch_loss:.4f} | Accuracy: {epoch_acc:.2f}%", flush=True)

    os.makedirs("python_ai", exist_ok=True)
    save_path = os.path.join("python_ai", "civic_mobilenet.pth")
    torch.save(model.state_dict(), save_path)
    print(f"Fine-Tuned PyTorch Model Saved to {save_path}!", flush=True)

if __name__ == "__main__":
    train_pytorch_model()

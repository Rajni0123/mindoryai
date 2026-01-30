"""
IMAGE SOURCING SYSTEM FOR WHITEBOARD VIDEOS
===============================================

Ye script automatically images download/generate karta hai
for professional whiteboard animations.

SOURCES (All FREE):
1. Iconify API - 100,000+ SVG icons
2. Undraw.co - Beautiful illustrations
3. SVGRepo - Free SVG library
4. Flaticon - Icons (with attribution)
5. AI Generation - Custom illustrations
"""

import os
import requests
import json
from pathlib import Path
from typing import Optional, List, Dict
import hashlib

# ============================================
# CONFIGURATION
# ============================================

ASSETS_BASE = Path("assets")
ICONS_DIR = ASSETS_BASE / "icons"
ILLUSTRATIONS_DIR = ASSETS_BASE / "illustrations"
HANDS_DIR = ASSETS_BASE / "hands"
DIAGRAMS_DIR = ASSETS_BASE / "diagrams"

# Create directories
for dir_path in [ICONS_DIR, ILLUSTRATIONS_DIR, HANDS_DIR, DIAGRAMS_DIR]:
    dir_path.mkdir(parents=True, exist_ok=True)


# ============================================
# 1. ICONIFY API (Best for icons)
# ============================================

class IconifyDownloader:
    """
    Download SVG icons from Iconify API
    100,000+ free icons available!
    
    Popular icon sets:
    - carbon: IBM Carbon icons (business/tech)
    - mdi: Material Design Icons
    - ph: Phosphor Icons
    - tabler: Tabler Icons
    - heroicons: Heroicons
    - lucide: Lucide Icons
    """
    
    BASE_URL = "https://api.iconify.design"
    
    # Common topic to icon mappings
    TOPIC_ICONS = {
        # AI & Technology
        "artificial intelligence": ["carbon:ai", "mdi:robot", "carbon:machine-learning"],
        "ai": ["carbon:ai", "mdi:robot-outline"],
        "machine learning": ["carbon:machine-learning", "mdi:brain"],
        "robot": ["mdi:robot", "carbon:bot"],
        "computer": ["carbon:laptop", "mdi:laptop"],
        "brain": ["mdi:brain", "carbon:idea"],
        "neural network": ["carbon:network-3-reference", "mdi:graph"],
        "data": ["carbon:data-base", "mdi:database"],
        "cloud": ["carbon:cloud", "mdi:cloud"],
        "internet": ["carbon:globe", "mdi:web"],
        "code": ["carbon:code", "mdi:code-tags"],
        "programming": ["carbon:terminal", "mdi:console"],
        "chatgpt": ["carbon:chat-bot", "mdi:robot"],
        "algorithm": ["carbon:flow", "mdi:sitemap"],
        
        # Science & Education
        "science": ["carbon:chemistry", "mdi:flask"],
        "chemistry": ["carbon:chemistry", "mdi:flask-outline"],
        "physics": ["mdi:atom", "carbon:meter"],
        "biology": ["carbon:dna", "mdi:dna"],
        "math": ["carbon:calculator", "mdi:calculator"],
        "formula": ["carbon:function", "mdi:function"],
        "experiment": ["carbon:flask", "mdi:test-tube"],
        "book": ["carbon:book", "mdi:book-open-page-variant"],
        "study": ["mdi:school", "carbon:education"],
        "exam": ["carbon:document-tasks", "mdi:file-document-edit"],
        "graduation": ["mdi:school", "carbon:education"],
        
        # Vehicles & Transport
        "car": ["carbon:car", "mdi:car"],
        "self driving car": ["mdi:car-connected", "carbon:car"],
        "vehicle": ["mdi:car", "carbon:vehicle-services"],
        "airplane": ["mdi:airplane", "carbon:airplane"],
        "rocket": ["mdi:rocket-launch", "carbon:rocket"],
        
        # Business
        "chart": ["carbon:chart-line", "mdi:chart-line"],
        "graph": ["carbon:chart-bar", "mdi:chart-bar"],
        "money": ["carbon:currency-dollar", "mdi:cash"],
        "growth": ["carbon:growth", "mdi:trending-up"],
        "target": ["carbon:target", "mdi:target"],
        "team": ["carbon:group", "mdi:account-group"],
        "meeting": ["carbon:events", "mdi:account-multiple"],
        
        # Common Objects
        "phone": ["carbon:phone", "mdi:cellphone"],
        "home": ["carbon:home", "mdi:home"],
        "settings": ["carbon:settings", "mdi:cog"],
        "search": ["carbon:search", "mdi:magnify"],
        "check": ["carbon:checkmark", "mdi:check"],
        "arrow": ["carbon:arrow-right", "mdi:arrow-right"],
        "lightbulb": ["carbon:idea", "mdi:lightbulb"],
        "heart": ["carbon:favorite", "mdi:heart"],
        "star": ["carbon:star", "mdi:star"],
        "user": ["carbon:user", "mdi:account"],
        "lock": ["carbon:locked", "mdi:lock"],
        "email": ["carbon:email", "mdi:email"],
        "calendar": ["carbon:calendar", "mdi:calendar"],
        "clock": ["carbon:time", "mdi:clock"],
        "location": ["carbon:location", "mdi:map-marker"],
        "camera": ["carbon:camera", "mdi:camera"],
        "microphone": ["carbon:microphone", "mdi:microphone"],
        "speaker": ["carbon:volume-up", "mdi:speaker"],
        "wifi": ["carbon:wifi", "mdi:wifi"],
        "battery": ["carbon:battery-full", "mdi:battery"],
        "download": ["carbon:download", "mdi:download"],
        "upload": ["carbon:upload", "mdi:upload"],
        "share": ["carbon:share", "mdi:share"],
        "edit": ["carbon:edit", "mdi:pencil"],
        "delete": ["carbon:trash-can", "mdi:delete"],
        "add": ["carbon:add", "mdi:plus"],
        "remove": ["carbon:subtract", "mdi:minus"],
    }
    
    @classmethod
    def search_icon(cls, query: str) -> List[str]:
        """Search for icons matching query"""
        query_lower = query.lower().strip()
        
        # Direct match
        if query_lower in cls.TOPIC_ICONS:
            return cls.TOPIC_ICONS[query_lower]
        
        # Partial match
        matches = []
        for topic, icons in cls.TOPIC_ICONS.items():
            if query_lower in topic or topic in query_lower:
                matches.extend(icons)
        
        return list(set(matches))[:3] if matches else []
    
    @classmethod
    def download_icon(cls, icon_id: str, save_name: str = None) -> Optional[Path]:
        """
        Download SVG icon from Iconify
        
        Args:
            icon_id: Icon identifier (e.g., "carbon:ai" or "mdi:robot")
            save_name: Optional custom filename
        
        Returns:
            Path to saved SVG file
        """
        try:
            prefix, name = icon_id.split(":")
            url = f"{cls.BASE_URL}/{prefix}/{name}.svg"
            
            response = requests.get(url, timeout=10)
            
            if response.status_code == 200:
                # Generate filename
                if save_name:
                    filename = f"{save_name}.svg"
                else:
                    filename = f"{prefix}_{name}.svg"
                
                save_path = ICONS_DIR / filename
                
                with open(save_path, 'w') as f:
                    f.write(response.text)
                
                print(f"[OK] Downloaded: {icon_id} -> {save_path}")
                return save_path
            else:
                print(f"[ERROR] Failed to download {icon_id}: HTTP {response.status_code}")
                
        except Exception as e:
            print(f"[ERROR] Error downloading {icon_id}: {e}")
        
        return None
    
    @classmethod
    def download_for_topic(cls, topic: str) -> Optional[Path]:
        """Download best icon for a topic"""
        icons = cls.search_icon(topic)
        
        if icons:
            # Download first matching icon
            return cls.download_icon(icons[0], topic.replace(" ", "_"))
        
        print(f"[WARN] No icon found for: {topic}")
        return None


# ============================================
# 2. HAND IMAGES DOWNLOAD
# ============================================

class HandImageDownloader:
    """
    Download hand images for whiteboard animation
    
    These are the specific hand images you need!
    """
    
    # URLs for free hand PNG images
    HAND_SOURCES = [
        {
            "name": "hand_marker_right",
            "description": "Right hand holding marker",
            "url": "https://www.pngall.com/wp-content/uploads/2016/04/Hand-Holding-Pen-PNG.png"
        },
        # Add more sources as needed
    ]
    
    @classmethod
    def download_hand(cls, url: str, name: str) -> Optional[Path]:
        """Download hand image"""
        try:
            response = requests.get(url, timeout=30)
            
            if response.status_code == 200:
                # Determine extension
                content_type = response.headers.get('content-type', '')
                ext = '.png' if 'png' in content_type else '.jpg'
                
                save_path = HANDS_DIR / f"{name}{ext}"
                
                with open(save_path, 'wb') as f:
                    f.write(response.content)
                
                print(f"[OK] Downloaded hand: {save_path}")
                return save_path
                
        except Exception as e:
            print(f"[ERROR] Error downloading hand: {e}")
        
        return None
    
    @classmethod
    def get_or_create_hand(cls) -> Path:
        """Get hand image, download if needed"""
        
        # Check if hand already exists
        existing_hands = list(HANDS_DIR.glob("hand_*.png"))
        if existing_hands:
            return existing_hands[0]
        
        # Try to download
        for source in cls.HAND_SOURCES:
            result = cls.download_hand(source["url"], source["name"])
            if result:
                return result
        
        # Create placeholder SVG
        return cls.create_svg_hand()
    
    @classmethod
    def create_svg_hand(cls) -> Path:
        """Create SVG hand as fallback"""
        svg_content = '''<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 250" width="200" height="250">
  <!-- Hand with pen - simplified -->
  <ellipse cx="100" cy="180" rx="50" ry="35" fill="#FDBCB4" stroke="#D4A090" stroke-width="2"/>
  
  <!-- Fingers -->
  <rect x="70" y="100" width="15" height="80" rx="7" fill="#FDBCB4" stroke="#D4A090"/>
  <rect x="90" y="90" width="15" height="90" rx="7" fill="#FDBCB4" stroke="#D4A090"/>
  <rect x="110" y="95" width="15" height="85" rx="7" fill="#FDBCB4" stroke="#D4A090"/>
  <rect x="130" y="105" width="12" height="70" rx="6" fill="#FDBCB4" stroke="#D4A090"/>
  
  <!-- Thumb -->
  <ellipse cx="55" cy="150" rx="12" ry="25" fill="#FDBCB4" stroke="#D4A090"/>
  
  <!-- Pen -->
  <rect x="82" y="20" width="10" height="85" fill="#333333"/>
  <polygon points="82,20 92,20 87,5" fill="#1a1a1a"/>
</svg>'''
        
        save_path = HANDS_DIR / "hand_default.svg"
        with open(save_path, 'w') as f:
            f.write(svg_content)
        
        print(f"[OK] Created SVG hand: {save_path}")
        return save_path


# ============================================
# 3. ILLUSTRATION GENERATORS
# ============================================

class DiagramGenerator:
    """
    Generate common diagram SVGs programmatically
    """
    
    @classmethod
    def create_process_flow_svg(cls, steps: List[str], filename: str) -> Path:
        """Create process flow diagram as SVG"""
        
        n = len(steps)
        width = 150 * n + 50
        height = 120
        
        svg_parts = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}">']
        
        for i, step in enumerate(steps):
            x = 50 + i * 150
            y = 30
            
            # Box
            svg_parts.append(f'''
            <rect x="{x}" y="{y}" width="120" height="60" rx="10" 
                  fill="#E8F4FD" stroke="#3498DB" stroke-width="2"/>
            <text x="{x+60}" y="{y+35}" text-anchor="middle" 
                  font-family="Arial" font-size="12" fill="#333">{step}</text>
            ''')
            
            # Arrow
            if i < n - 1:
                arrow_x = x + 120
                svg_parts.append(f'''
                <line x1="{arrow_x}" y1="{y+30}" x2="{arrow_x+25}" y2="{y+30}" 
                      stroke="#666" stroke-width="2"/>
                <polygon points="{arrow_x+25},{y+25} {arrow_x+35},{y+30} {arrow_x+25},{y+35}" 
                         fill="#666"/>
                ''')
        
        svg_parts.append('</svg>')
        
        save_path = DIAGRAMS_DIR / f"{filename}.svg"
        with open(save_path, 'w') as f:
            f.write('\n'.join(svg_parts))
        
        return save_path
    
    @classmethod
    def create_comparison_svg(cls, item1: str, item2: str, filename: str) -> Path:
        """Create VS comparison diagram"""
        
        svg = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 150">
        <!-- Left box -->
        <rect x="20" y="30" width="150" height="80" rx="10" 
              fill="#E8F4FD" stroke="#3498DB" stroke-width="2"/>
        <text x="95" y="75" text-anchor="middle" 
              font-family="Arial" font-size="16" font-weight="bold" fill="#333">{item1}</text>
        
        <!-- VS circle -->
        <circle cx="200" cy="70" r="25" fill="#E74C3C"/>
        <text x="200" y="77" text-anchor="middle" 
              font-family="Arial" font-size="14" font-weight="bold" fill="white">VS</text>
        
        <!-- Right box -->
        <rect x="230" y="30" width="150" height="80" rx="10" 
              fill="#FDE8E8" stroke="#E74C3C" stroke-width="2"/>
        <text x="305" y="75" text-anchor="middle" 
              font-family="Arial" font-size="16" font-weight="bold" fill="#333">{item2}</text>
        </svg>'''
        
        save_path = DIAGRAMS_DIR / f"{filename}.svg"
        with open(save_path, 'w') as f:
            f.write(svg)
        
        return save_path


# ============================================
# 4. MAIN ASSET MANAGER
# ============================================

class AssetManager:
    """
    Unified interface to get any asset for whiteboard video
    """
    
    @classmethod
    def get_icon(cls, topic: str) -> Optional[Path]:
        """Get icon for a topic"""
        
        # Check cache first
        cached = list(ICONS_DIR.glob(f"{topic.replace(' ', '_')}*.svg"))
        if cached:
            return cached[0]
        
        # Download
        return IconifyDownloader.download_for_topic(topic)
    
    @classmethod
    def get_hand(cls) -> Path:
        """Get hand image"""
        return HandImageDownloader.get_or_create_hand()
    
    @classmethod
    def get_process_diagram(cls, steps: List[str], name: str) -> Path:
        """Get process flow diagram"""
        return DiagramGenerator.create_process_flow_svg(steps, name)
    
    @classmethod
    def get_comparison_diagram(cls, item1: str, item2: str, name: str) -> Path:
        """Get comparison diagram"""
        return DiagramGenerator.create_comparison_svg(item1, item2, name)
    
    @classmethod
    def download_all_for_content(cls, content_items: List[Dict]):
        """
        Pre-download all assets needed for content
        
        Args:
            content_items: List of content items with icon_topic fields
        """
        print("[ASSETS] Downloading assets...")
        
        # Download hand first
        hand = cls.get_hand()
        print(f"  Hand: {hand}")
        
        # Download icons for each topic
        topics_seen = set()
        for item in content_items:
            topic = item.get("icon_topic") or item.get("concept")
            if topic and topic not in topics_seen:
                topics_seen.add(topic)
                icon = cls.get_icon(topic)
                if icon:
                    print(f"  Icon [{topic}]: {icon}")
        
        print("[ASSETS] Assets ready!")


# ============================================
# CLI TOOL
# ============================================

def main():
    import sys
    
    print("=" * 60)
    print("Whiteboard Asset Downloader")
    print("=" * 60)
    
    if len(sys.argv) < 2:
        print("""
Usage:
  python image_sources.py icon <topic>     - Download icon for topic
  python image_sources.py hand             - Download hand image
  python image_sources.py all              - Download common assets
  python image_sources.py search <query>   - Search for icons

Examples:
  python image_sources.py icon "artificial intelligence"
  python image_sources.py icon robot
  python image_sources.py hand
  python image_sources.py search brain
        """)
        return
    
    command = sys.argv[1].lower()
    
    if command == "icon" and len(sys.argv) > 2:
        topic = " ".join(sys.argv[2:])
        path = AssetManager.get_icon(topic)
        if path:
            print(f"\n[OK] Icon saved: {path}")
    
    elif command == "hand":
        path = AssetManager.get_hand()
        print(f"\n[OK] Hand saved: {path}")
    
    elif command == "search" and len(sys.argv) > 2:
        query = " ".join(sys.argv[2:])
        results = IconifyDownloader.search_icon(query)
        print(f"\n[SEARCH] Results for '{query}':")
        for icon in results:
            print(f"  - {icon}")
    
    elif command == "all":
        # Download common educational assets
        common_topics = [
            "ai", "robot", "brain", "computer", "book", 
            "chart", "target", "lightbulb", "check", "car"
        ]
        print("\n[DOWNLOAD] Downloading common assets...")
        for topic in common_topics:
            AssetManager.get_icon(topic)
        AssetManager.get_hand()
        print("\n[DONE] All done!")
    
    else:
        print("[ERROR] Unknown command. Run without arguments for help.")


if __name__ == "__main__":
    main()

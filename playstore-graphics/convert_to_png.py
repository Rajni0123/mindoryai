import base64
import os

# Get the directory path
base_dir = r"I:\heris the code"
output_dir = r"I:\heris the code\playstore-graphics\output"

# Create output directory
os.makedirs(output_dir, exist_ok=True)

def image_to_base64(image_path):
    """Convert image to base64 string"""
    with open(image_path, "rb") as img_file:
        return base64.b64encode(img_file.read()).decode('utf-8')

# Load all screenshots as base64
screenshots = {
    "screen1": image_to_base64(os.path.join(base_dir, "screen1.jpeg")),
    "scan_page": image_to_base64(os.path.join(base_dir, "scan page.jpeg")),
    "solucti": image_to_base64(os.path.join(base_dir, "solucti.jpeg")),
    "quiz": image_to_base64(os.path.join(base_dir, "quiz.jpeg")),
    "whiteboard": image_to_base64(os.path.join(base_dir, "whiteboard screen.jpeg")),
    "soluction_page": image_to_base64(os.path.join(base_dir, "soluction page.jpeg")),
}

def create_screenshot_html(title, subtitle, badge, image_key, gradient, num):
    """Generate screenshot HTML with embedded image"""
    return f'''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Screenshot {num}</title>
    <style>
        * {{ margin: 0; padding: 0; box-sizing: border-box; }}
        body {{ width: 1080px; height: 1920px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }}
        .container {{
            width: 1080px; height: 1920px;
            background: {gradient};
            display: flex; flex-direction: column; align-items: center; justify-content: space-between;
            padding: 80px 60px; position: relative; overflow: hidden;
        }}
        .circle1 {{ position: absolute; width: 400px; height: 400px; background: rgba(255,255,255,0.06); border-radius: 50%; top: -100px; right: -100px; }}
        .circle2 {{ position: absolute; width: 300px; height: 300px; background: rgba(245, 158, 11, 0.1); border-radius: 50%; bottom: 300px; left: -100px; }}
        .header-text {{ text-align: center; z-index: 10; }}
        .header-text h2 {{ color: white; font-size: 72px; font-weight: 800; margin-bottom: 20px; line-height: 1.15; }}
        .header-text p {{ color: rgba(255,255,255,0.9); font-size: 36px; }}
        .phone-frame {{
            width: 580px; height: 1150px; background: #1a1a1a; border-radius: 55px; padding: 10px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.5); z-index: 10; position: relative;
        }}
        .phone-screen {{ width: 100%; height: 100%; border-radius: 45px; overflow: hidden; background: #f0fdf4; }}
        .phone-screen img {{ width: 100%; height: 100%; object-fit: cover; object-position: top center; }}
        .phone-frame::before {{
            content: ''; position: absolute; top: 20px; left: 50%; transform: translateX(-50%);
            width: 150px; height: 35px; background: #1a1a1a; border-radius: 0 0 20px 20px; z-index: 20;
        }}
        .badge {{
            background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 24px 48px;
            border-radius: 50px; color: white; font-size: 32px; font-weight: 600; z-index: 10;
            border: 2px solid rgba(255,255,255,0.3);
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="circle1"></div>
        <div class="circle2"></div>
        <div class="header-text">
            <h2>{title}</h2>
            <p>{subtitle}</p>
        </div>
        <div class="phone-frame">
            <div class="phone-screen">
                <img src="data:image/jpeg;base64,{screenshots[image_key]}" alt="Screenshot">
            </div>
        </div>
        <div class="badge">{badge}</div>
    </div>
</body>
</html>'''

# Screenshot configurations
configs = [
    ("Your AI Study<br>Companion", "Everything you need to excel", "📚 All-in-One Learning", "screen1", "linear-gradient(180deg, #0D9488 0%, #0F766E 50%, #115E59 100%)", 1),
    ("Scan Any Problem<br>Get Instant Solution", "Point, scan, learn!", "📷 AI-Powered Scanner", "scan_page", "linear-gradient(180deg, #115E59 0%, #0F766E 50%, #0D9488 100%)", 2),
    ("Step-by-Step<br>Solutions", "Understand every concept", "✅ Detailed Explanations", "solucti", "linear-gradient(180deg, #0D9488 0%, #14B8A6 50%, #2DD4BF 100%)", 3),
    ("AI-Generated<br>Quizzes", "Test your knowledge instantly", "🧠 Smart Practice Mode", "quiz", "linear-gradient(180deg, #F59E0B 0%, #D97706 50%, #B45309 100%)", 4),
    ("Whiteboard<br>Video Lessons", "Visual learning made easy", "🎬 Animated Explanations", "whiteboard", "linear-gradient(180deg, #0F766E 0%, #0D9488 50%, #14B8A6 100%)", 5),
    ("Complex Problems<br>Simplified", "From LCM to Calculus", "🔢 All Subjects Covered", "soluction_page", "linear-gradient(180deg, #1E3A5F 0%, #0F766E 50%, #0D9488 100%)", 6),
]

# Generate HTML files with embedded images
for title, subtitle, badge, image_key, gradient, num in configs:
    html_content = create_screenshot_html(title, subtitle, badge, image_key, gradient, num)
    output_path = os.path.join(output_dir, f"screenshot-{num}-embedded.html")
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(html_content)
    print(f"Created: screenshot-{num}-embedded.html")

# Create Feature Graphic with embedded phone screenshot
feature_html = f'''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BlinkStudy Feature Graphic</title>
    <style>
        * {{ margin: 0; padding: 0; box-sizing: border-box; }}
        body {{ width: 1024px; height: 500px; overflow: hidden; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }}
        .container {{
            width: 1024px; height: 500px;
            background: linear-gradient(135deg, #0D9488 0%, #0F766E 50%, #115E59 100%);
            display: flex; align-items: center; justify-content: space-between;
            padding: 40px 60px; position: relative; overflow: hidden;
        }}
        .circle1 {{ position: absolute; width: 300px; height: 300px; background: rgba(255,255,255,0.05); border-radius: 50%; top: -100px; right: -50px; }}
        .circle2 {{ position: absolute; width: 200px; height: 200px; background: rgba(255,255,255,0.03); border-radius: 50%; bottom: -80px; left: 100px; }}
        .left-content {{ flex: 1; z-index: 10; padding-right: 40px; }}
        .logo-section {{ display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }}
        .logo-icon {{
            width: 70px; height: 70px; background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 900; color: white; box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }}
        .logo-icon .s {{ color: #F59E0B; }}
        .app-name {{ font-size: 42px; font-weight: 800; color: white; letter-spacing: -1px; }}
        .app-name span {{ color: #F59E0B; }}
        .tagline {{ font-size: 28px; color: white; font-weight: 600; margin-bottom: 15px; line-height: 1.3; }}
        .subtitle {{ font-size: 18px; color: rgba(255,255,255,0.85); line-height: 1.5; margin-bottom: 30px; }}
        .features {{ display: flex; gap: 15px; flex-wrap: wrap; }}
        .feature-badge {{
            background: rgba(255,255,255,0.15); padding: 10px 18px; border-radius: 25px;
            color: white; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px;
            border: 1px solid rgba(255,255,255,0.2);
        }}
        .right-content {{ position: relative; z-index: 10; }}
        .phone-mockup {{
            width: 200px; height: 400px; background: #1a1a1a; border-radius: 30px; padding: 6px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4); transform: rotate(5deg);
        }}
        .phone-screen {{ width: 100%; height: 100%; background: #f0fdf4; border-radius: 25px; overflow: hidden; }}
        .phone-screen img {{ width: 100%; height: 100%; object-fit: cover; object-position: top; }}
        .rating-badge {{
            position: absolute; bottom: -15px; left: 50%; transform: translateX(-50%);
            background: white; padding: 8px 18px; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: flex; align-items: center; gap: 5px; font-size: 14px; font-weight: 700; color: #1F2937;
        }}
        .rating-badge .stars {{ color: #F59E0B; }}
    </style>
</head>
<body>
    <div class="container">
        <div class="circle1"></div>
        <div class="circle2"></div>
        <div class="left-content">
            <div class="logo-section">
                <div class="logo-icon">B<span class="s">S</span></div>
                <div class="app-name">Blink<span>Study</span></div>
            </div>
            <div class="tagline">Your AI-Powered<br>Study Companion</div>
            <div class="subtitle">Scan problems, get instant solutions.<br>Learn smarter with AI quizzes & whiteboard videos.</div>
            <div class="features">
                <div class="feature-badge"><span>📷</span> Scan & Solve</div>
                <div class="feature-badge"><span>🧠</span> AI Quiz</div>
                <div class="feature-badge"><span>🎬</span> Whiteboard</div>
                <div class="feature-badge"><span>💬</span> AI Chat</div>
            </div>
        </div>
        <div class="right-content">
            <div class="phone-mockup">
                <div class="phone-screen">
                    <img src="data:image/jpeg;base64,{screenshots["screen1"]}" alt="App">
                </div>
            </div>
            <div class="rating-badge"><span class="stars">★★★★★</span><span>4.8</span></div>
        </div>
    </div>
</body>
</html>'''

with open(os.path.join(output_dir, "feature-graphic-embedded.html"), 'w', encoding='utf-8') as f:
    f.write(feature_html)
print("Created: feature-graphic-embedded.html")

print(f"\n✅ All HTML files with embedded images created in: {output_dir}")
print("\nNext step: Run convert_html_to_png.py to generate PNG files")

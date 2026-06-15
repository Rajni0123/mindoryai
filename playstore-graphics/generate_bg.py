"""
Generate Background Only - No content
"""
import os
import asyncio
from playwright.async_api import async_playwright

output_dir = r"I:\heris the code\playstore-graphics\png"
os.makedirs(output_dir, exist_ok=True)

# Feature Graphic BG - 1024x500
feature_bg_html = '''<!DOCTYPE html>
<html>
<head>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { width: 1024px; height: 500px; }
        .bg {
            width: 1024px;
            height: 500px;
            background: linear-gradient(135deg, #0D9488 0%, #0F766E 50%, #115E59 100%);
            position: relative;
            overflow: hidden;
        }
        .circle1 {
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            top: -100px;
            right: -50px;
        }
        .circle2 {
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            bottom: -80px;
            left: 100px;
        }
        .circle3 {
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(245, 158, 11, 0.1);
            border-radius: 50%;
            top: 150px;
            left: 400px;
        }
        .circle4 {
            position: absolute;
            width: 250px;
            height: 250px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
            bottom: -50px;
            right: 200px;
        }
    </style>
</head>
<body>
    <div class="bg">
        <div class="circle1"></div>
        <div class="circle2"></div>
        <div class="circle3"></div>
        <div class="circle4"></div>
    </div>
</body>
</html>'''

# Screenshot BG - 1080x1920
screenshot_bg_html = '''<!DOCTYPE html>
<html>
<head>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { width: 1080px; height: 1920px; }
        .bg {
            width: 1080px;
            height: 1920px;
            background: linear-gradient(180deg, #0D9488 0%, #0F766E 50%, #115E59 100%);
            position: relative;
            overflow: hidden;
        }
        .circle1 {
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }
        .circle2 {
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(245, 158, 11, 0.1);
            border-radius: 50%;
            bottom: 300px;
            left: -100px;
        }
        .circle3 {
            position: absolute;
            width: 250px;
            height: 250px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            top: 600px;
            right: -50px;
        }
        .circle4 {
            position: absolute;
            width: 350px;
            height: 350px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
            bottom: -100px;
            left: 200px;
        }
    </style>
</head>
<body>
    <div class="bg">
        <div class="circle1"></div>
        <div class="circle2"></div>
        <div class="circle3"></div>
        <div class="circle4"></div>
    </div>
</body>
</html>'''

async def generate_bgs():
    async with async_playwright() as p:
        browser = await p.chromium.launch()

        # Feature Graphic BG 1024x500
        page = await browser.new_page(viewport={'width': 1024, 'height': 500})
        await page.set_content(feature_bg_html)
        await page.screenshot(path=os.path.join(output_dir, "bg-feature-1024x500.png"))
        print("Created: bg-feature-1024x500.png")
        await page.close()

        # Screenshot BG 1080x1920
        page = await browser.new_page(viewport={'width': 1080, 'height': 1920})
        await page.set_content(screenshot_bg_html)
        await page.screenshot(path=os.path.join(output_dir, "bg-screenshot-1080x1920.png"))
        print("Created: bg-screenshot-1080x1920.png")
        await page.close()

        await browser.close()

    print(f"\nBG files saved to: {output_dir}")

if __name__ == "__main__":
    asyncio.run(generate_bgs())

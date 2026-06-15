"""
Generate BlinkStudy App Icon 512x512 PNG
"""
import os
import asyncio
from playwright.async_api import async_playwright

output_dir = r"I:\heris the code\playstore-graphics\png"
os.makedirs(output_dir, exist_ok=True)

# App Icon HTML - 512x512
app_icon_html = '''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            width: 512px;
            height: 512px;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .icon {
            width: 512px;
            height: 512px;
            background: linear-gradient(145deg, #0D9488 0%, #0F766E 60%, #115E59 100%);
            border-radius: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles */
        .circle1 {
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            top: -50px;
            right: -30px;
        }
        .circle2 {
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            bottom: -40px;
            left: -30px;
        }
        .circle3 {
            position: absolute;
            width: 100px;
            height: 100px;
            background: rgba(245, 158, 11, 0.15);
            border-radius: 50%;
            top: 80px;
            left: 50px;
        }

        /* Letters container */
        .letters {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            z-index: 10;
        }

        .letter-b {
            font-size: 220px;
            font-weight: 900;
            color: #FFFFFF;
            text-shadow:
                4px 4px 0 rgba(0,0,0,0.15),
                0 8px 20px rgba(0,0,0,0.2);
            line-height: 1;
        }

        .letter-s {
            font-size: 220px;
            font-weight: 900;
            color: #F59E0B;
            text-shadow:
                4px 4px 0 rgba(0,0,0,0.15),
                0 8px 20px rgba(0,0,0,0.2);
            line-height: 1;
        }

        /* Shine effect */
        .shine {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(180deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 100%);
            border-radius: 110px 110px 0 0;
        }

        /* Small sparkle */
        .sparkle {
            position: absolute;
            top: 70px;
            right: 90px;
            width: 30px;
            height: 30px;
            z-index: 20;
        }
        .sparkle::before, .sparkle::after {
            content: '';
            position: absolute;
            background: #F59E0B;
        }
        .sparkle::before {
            width: 4px;
            height: 30px;
            left: 13px;
            top: 0;
            border-radius: 2px;
        }
        .sparkle::after {
            width: 30px;
            height: 4px;
            left: 0;
            top: 13px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="icon">
        <div class="circle1"></div>
        <div class="circle2"></div>
        <div class="circle3"></div>
        <div class="shine"></div>
        <div class="sparkle"></div>

        <div class="letters">
            <span class="letter-b">B</span>
            <span class="letter-s">S</span>
        </div>
    </div>
</body>
</html>'''

async def generate_icon():
    async with async_playwright() as p:
        browser = await p.chromium.launch()

        # Create 512x512 icon
        page = await browser.new_page(viewport={'width': 512, 'height': 512})
        await page.set_content(app_icon_html)

        # Save as PNG
        icon_path = os.path.join(output_dir, "app-icon-512.png")
        await page.screenshot(path=icon_path, omit_background=False)
        print(f"Created: app-icon-512.png (512x512)")

        await page.close()
        await browser.close()

    print(f"\nApp icon saved to: {icon_path}")

if __name__ == "__main__":
    asyncio.run(generate_icon())

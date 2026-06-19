"""
Convert HTML files to PNG using Playwright
Install: pip install playwright && playwright install chromium
"""
import os
import asyncio

try:
    from playwright.async_api import async_playwright
except ImportError:
    print("Installing playwright...")
    os.system("pip install playwright")
    os.system("playwright install chromium")
    from playwright.async_api import async_playwright

output_dir = r"I:\heris the code\playstore-graphics\output"
png_dir = r"I:\heris the code\playstore-graphics\png"

os.makedirs(png_dir, exist_ok=True)

async def convert_to_png():
    async with async_playwright() as p:
        browser = await p.chromium.launch()

        # Convert screenshots (1080x1920)
        for i in range(1, 7):
            html_path = os.path.join(output_dir, f"screenshot-{i}-embedded.html")
            png_path = os.path.join(png_dir, f"screenshot-{i}.png")

            page = await browser.new_page(viewport={'width': 1080, 'height': 1920})
            await page.goto(f"file:///{html_path.replace(os.sep, '/')}")
            await page.screenshot(path=png_path, full_page=False)
            await page.close()
            print(f"Created: screenshot-{i}.png (1080x1920)")

        # Convert feature graphic (1024x500)
        html_path = os.path.join(output_dir, "feature-graphic-embedded.html")
        png_path = os.path.join(png_dir, "feature-graphic.png")

        page = await browser.new_page(viewport={'width': 1024, 'height': 500})
        await page.goto(f"file:///{html_path.replace(os.sep, '/')}")
        await page.screenshot(path=png_path, full_page=False)
        await page.close()
        print("Created: feature-graphic.png (1024x500)")

        await browser.close()

    print(f"\nAll PNG files saved to: {png_dir}")

if __name__ == "__main__":
    asyncio.run(convert_to_png())

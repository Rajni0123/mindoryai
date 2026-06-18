====================================================
        BLINKSTUDY - PLAY STORE GRAPHICS
====================================================

Created graphics for Google Play Store submission.
Color Theme: Teal (#0D9488) + Orange Accent (#F59E0B)

====================================================
FILES INCLUDED:
====================================================

1. FEATURE GRAPHIC (1024 x 500 px)
   - File: feature-graphic.html
   - Open in Chrome browser
   - Press F12 → Device Toolbar → Set to 1024x500
   - Take screenshot or use Chrome DevTools screenshot

2. APP SCREENSHOTS (1080 x 1920 px each)
   - screenshot-1.html → Home Screen
   - screenshot-2.html → Scan & Solve
   - screenshot-3.html → Step-by-Step Solutions
   - screenshot-4.html → AI Quiz
   - screenshot-5.html → Whiteboard Video
   - screenshot-6.html → Advanced Solutions

   Each file is exactly 1080x1920px (Play Store required size)

3. APP ICON (512 x 512 px)
   - File: app-icon-512.html
   - Or use existing: new logo app.png (already perfect!)

4. PREVIEW ALL SCREENSHOTS
   - File: screenshots.html
   - Shows all 6 screenshots in smaller preview

====================================================
HOW TO EXPORT AS PNG:
====================================================

METHOD 1: Browser Screenshot (Recommended)
------------------------------------------
1. Open HTML file in Google Chrome
2. Press F12 to open DevTools
3. Press Ctrl+Shift+P, type "screenshot"
4. Select "Capture full size screenshot"
5. PNG will download automatically

METHOD 2: Online Converter
--------------------------
1. Open: https://www.hiqpdf.com/demo/ConvertHtmlToImage.aspx
2. Upload HTML file
3. Set dimensions (1080x1920 for screenshots)
4. Download PNG

METHOD 3: Using Node.js (puppeteer)
-----------------------------------
npm install puppeteer
Then run:

const puppeteer = require('puppeteer');
(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  await page.setViewport({width: 1080, height: 1920});
  await page.goto('file:///path/to/screenshot-1.html');
  await page.screenshot({path: 'screenshot-1.png'});
  await browser.close();
})();

====================================================
PLAY STORE REQUIREMENTS:
====================================================

✓ Feature Graphic: 1024 x 500 px (JPEG or PNG)
✓ Screenshots: 1080 x 1920 px (min 2, max 8)
✓ App Icon: 512 x 512 px (PNG, 32-bit)
✓ Short Description: Max 80 characters
✓ Full Description: Max 4000 characters

====================================================
SUGGESTED PLAY STORE TEXT:
====================================================

SHORT DESCRIPTION (80 chars):
"AI study companion - Scan problems, get solutions, take quizzes & watch videos!"

FULL DESCRIPTION:
BlinkStudy - Your AI-Powered Study Companion

⚠️ DISCLAIMER: BlinkStudy is an independent educational technology platform and is NOT affiliated with, endorsed by, or representing any government body or official examination authority. We are a private entity providing AI-powered study assistance tools.

📷 SCAN & SOLVE
Point your camera at any math problem or question and get instant step-by-step solutions powered by AI.

🧠 SMART QUIZZES
Generate AI-powered quizzes on any topic. Practice with MCQs tailored to your learning level - Easy, Medium, or Hard.

🎬 WHITEBOARD VIDEOS
Watch animated explanations that break down complex concepts into easy-to-understand visual lessons.

💬 AI CHAT
Ask any study-related question and get detailed explanations instantly from our AI tutor.

📚 ALL SUBJECTS COVERED
- Mathematics (Algebra, Calculus, Geometry)
- Physics (Mechanics, Electromagnetism)
- Chemistry
- Reasoning (Logical, Verbal, Analytical)
- And more!

✨ KEY FEATURES:
• Instant problem solving with camera
• Step-by-step detailed solutions
• AI-generated practice quizzes
• Whiteboard video explanations
• Multi-language support (English & Hindi)
• Dark mode support
• Offline access to saved content

Perfect for:
• JEE & NEET preparation
• CBSE, ICSE board exams
• Competitive exam practice
• Daily homework help

📋 OFFICIAL GOVERNMENT SOURCES:
For official examination information, visit:
• JEE Main: https://jeemain.nta.nic.in/
• NEET: https://neet.nta.nic.in/
• CBSE: https://www.cbse.gov.in/
• UPSC: https://upsc.gov.in/
• NCERT: https://ncert.nic.in/

Download BlinkStudy now and transform the way you learn!

====================================================

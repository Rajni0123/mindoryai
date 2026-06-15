# BlinkStudy Brand Book
## Comprehensive Brand & Technical Documentation

---

# 1. BRAND IDENTITY

## 1.1 Logo
- **Primary Logo**: Located at `public/logo.png`
- **Favicon**: `public/favicon.ico`
- **App Icon**: `mindory-app/assets/icon.png`
- **Adaptive Icon**: `mindory-app/assets/adaptive-icon.png`
- **Splash Screen**: `mindory-app/assets/splash-icon.png`

## 1.2 Color Palette

### Primary Colors
| Color Name | Hex Code | Usage |
|------------|----------|-------|
| **Primary Teal** | `#0D9488` | Main brand color, CTAs, headers |
| **Primary Light** | `#14B8A6` | Hover states, accents |
| **Primary Dark** | `#0F766E` | Active states, emphasis |

### Secondary Colors
| Color Name | Hex Code | Usage |
|------------|----------|-------|
| **Secondary Amber** | `#F59E0B` | Highlights, warnings, premium features |
| **Secondary Light** | `#FBBF24` | Badges, notifications |
| **Secondary Dark** | `#D97706` | Active secondary elements |

### Accent Colors
| Color Name | Hex Code | Usage |
|------------|----------|-------|
| **Indigo** | `#6366F1` | Links, interactive elements |
| **Purple** | `#8B5CF6` | AI features, premium |
| **Pink** | `#EC4899` | Promotions, special offers |
| **Green** | `#22C55E` | Success states, correct answers |
| **Red** | `#EF4444` | Errors, wrong answers |

### Neutral Grayscale
| Name | Hex Code | Usage |
|------|----------|-------|
| Gray 50 | `#F9FAFB` | Backgrounds |
| Gray 100 | `#F3F4F6` | Card backgrounds |
| Gray 200 | `#E5E7EB` | Borders |
| Gray 300 | `#D1D5DB` | Disabled states |
| Gray 400 | `#9CA3AF` | Placeholder text |
| Gray 500 | `#6B7280` | Secondary text |
| Gray 600 | `#4B5563` | Body text |
| Gray 700 | `#374151` | Headings |
| Gray 800 | `#1F2937` | Dark backgrounds |
| Gray 900 | `#111827` | Darkest elements |

### Dark Mode Theme
| Element | Color |
|---------|-------|
| Background | `#0F172A` (Slate 900) |
| Surface | `#1E293B` (Slate 800) |
| Border | `#334155` (Slate 700) |
| Text Primary | `#F8FAFC` (Slate 50) |
| Text Secondary | `#94A3B8` (Slate 400) |

## 1.3 Typography

### Font Family
- **Primary Font**: `Outfit` (Google Fonts)
- **Fallback**: `system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`
- **Monospace**: `'Fira Code', 'JetBrains Mono', monospace`

### Font Weights
| Weight | Value | Usage |
|--------|-------|-------|
| Regular | 400 | Body text |
| Medium | 500 | Subtitles, emphasis |
| Semi-Bold | 600 | Buttons, labels |
| Bold | 700 | Headings, titles |

### Font Sizes
| Name | Size | Line Height | Usage |
|------|------|-------------|-------|
| xs | 12px | 16px | Captions, metadata |
| sm | 14px | 20px | Secondary text |
| base | 16px | 24px | Body text |
| lg | 18px | 28px | Large body |
| xl | 20px | 28px | Section titles |
| 2xl | 24px | 32px | Page subtitles |
| 3xl | 30px | 36px | Page titles |
| 4xl | 36px | 40px | Hero headings |
| 5xl | 48px | 1 | Large headings |

---

# 2. PRODUCT OVERVIEW

## 2.1 Brand Information
- **App Name**: BlinkStudy
- **Tagline**: "AI-Powered Learning Platform"
- **Package ID**: `com.blinkstudy.app`
- **Current Version**: 1.2.1 (versionCode: 21)
- **Website**: https://blinkstudy.in

## 2.2 Target Audience
- Students (Class 6-12)
- Competitive exam aspirants (JEE, NEET, UPSC)
- Self-learners seeking AI tutoring

## 2.3 Core Value Proposition
- Instant doubt solving with AI
- Personalized learning experience
- Multi-language support (English, Hindi, Hinglish)
- Visual learning with whiteboard videos

---

# 3. FEATURES CATALOG

## 3.1 AI Chat Features
| Feature | Description |
|---------|-------------|
| **AI Doubt Solver** | Instant answers to academic questions |
| **Multi-AI Providers** | Gemini, OpenAI (GPT-4), Claude, DeepSeek, Grok |
| **Image Analysis** | Scan handwritten notes/documents |
| **Voice Input** | Speech-to-text question input |
| **Text-to-Speech** | AI responses read aloud |
| **Chat History** | Saved conversations with search |
| **Conversation Sharing** | Export chats as PDF |

## 3.2 Quiz & Assessment
| Feature | Description |
|---------|-------------|
| **AI Quiz Generator** | Create quizzes from any topic |
| **Topic-based Quizzes** | Pre-built subject quizzes |
| **Timer Mode** | Timed assessments |
| **Difficulty Levels** | Easy, Medium, Hard |
| **Performance Analytics** | Track progress and scores |
| **Daily Challenge** | Daily quiz competitions |

## 3.3 Exam Preparation
| Feature | Description |
|---------|-------------|
| **Exam Prep Module** | JEE, NEET, UPSC preparation |
| **Previous Year Questions** | CBSE PYQs database |
| **Practice Tests** | Mock exams with solutions |
| **Study Materials** | Curated study resources |

## 3.4 Visual Learning
| Feature | Description |
|---------|-------------|
| **Whiteboard Videos** | AI-generated explanatory videos |
| **Step-by-step Solutions** | Visual problem solving |
| **Manim Animations** | Mathematical visualizations |
| **Video Downloads** | Offline access to videos |

## 3.5 Gamification
| Feature | Description |
|---------|-------------|
| **Study Battles** | Real-time multiplayer quizzes |
| **Leaderboards** | Compete with other students |
| **Achievements** | Earn badges and rewards |
| **Streaks** | Daily learning streaks |
| **Points System** | Earn credits for activities |

## 3.6 Personalization
| Feature | Description |
|---------|-------------|
| **AI Personalities** | Choose tutor personality |
| **Learning Preferences** | Customize AI responses |
| **Favorite Topics** | Quick access to interests |
| **Dark Mode** | Eye-friendly dark theme |
| **Language Selection** | English/Hindi/Hinglish |

## 3.7 Additional Features
| Feature | Description |
|---------|-------------|
| **Push Notifications** | Study reminders |
| **Offline Mode** | Access cached content |
| **PDF Export** | Export chats and notes |
| **Calculator** | Built-in scientific calculator |
| **Notes** | Personal note-taking |

---

# 4. TECHNICAL ARCHITECTURE

## 4.1 Technology Stack

### Backend
| Component | Technology |
|-----------|------------|
| Framework | Laravel 11.x (PHP 8.2+) |
| Database | MySQL 8.0 |
| Cache | Redis (Primary), File (Fallback) |
| Queue | Redis Queue with Supervisor |
| Authentication | Laravel Sanctum |
| API | RESTful JSON API |

### Frontend Web
| Component | Technology |
|-----------|------------|
| Framework | Laravel Blade Templates |
| CSS | Tailwind CSS 3.x |
| JavaScript | Alpine.js, Vanilla JS |
| Icons | Heroicons, Lucide |

### Mobile App
| Component | Technology |
|-----------|------------|
| Framework | React Native + Expo SDK 52 |
| State Management | React Context + AsyncStorage |
| Navigation | React Navigation 7.x |
| UI Components | Custom + NativeWind |
| Animations | Reanimated 3 |

### AI Services
| Provider | Models Used |
|----------|-------------|
| Google | Gemini 2.0 Flash, Gemini Pro |
| OpenAI | GPT-4o, GPT-4o-mini |
| Anthropic | Claude 3.5 Sonnet |
| DeepSeek | DeepSeek Chat |
| xAI | Grok |

## 4.2 Database Schema (60+ Models)

### Core Models
```
User, Admin, Student
Subscription, Plan, Payment
Chat, ChatMessage, MobileChat
Quiz, QuizQuestion, QuizAttempt
Exam, ExamQuestion, ExamAttempt
```

### Feature Models
```
WhiteboardVideo, VideoRenderJob
StudyBattleRoom, StudyBattleParticipant
DailyChallenge, DailyChallengeAttempt
Note, Favorite, Achievement
```

### Configuration Models
```
Setting, FrontendConfig, MobileAppConfig
HomepageSetting, AiModel, AiPersonality
```

## 4.3 API Endpoints (70+ Endpoints)

### Authentication
```
POST /api/send-otp
POST /api/verify-otp
POST /api/login
POST /api/logout
GET  /api/user
```

### AI Chat
```
POST /api/mobile-chat
POST /api/mobile-chat/stream
GET  /api/chat-history
POST /api/analyze-image
POST /api/text-to-speech
```

### Quiz
```
POST /api/generate-quiz
GET  /api/quizzes
POST /api/quiz/{id}/submit
GET  /api/quiz-history
```

### Payments
```
POST /api/create-order
POST /api/verify-payment
GET  /api/plans
GET  /api/subscription
```

### Configuration
```
GET  /api/app-config
GET  /api/frontend-config
GET  /api/ai-models
```

## 4.4 Services Architecture (41 Services)

### AI Services
```php
GeminiService        // Primary AI provider
OpenAIService        // GPT-4 integration
ClaudeService        // Anthropic Claude
DeepSeekService      // DeepSeek AI
GrokService          // xAI Grok
UnifiedAIService     // Multi-provider router
StudentDoubtSolverService  // Educational AI wrapper
```

### Core Services
```php
CreditService        // Usage credits management
UsageLimitService    // Rate limiting
SubscriptionService  // Plan management
PaymentService       // Payment processing
OTPService           // Authentication
```

### Feature Services
```php
QuizGeneratorService     // AI quiz creation
WhiteboardVideoService   // Video generation
ManimVideoService        // Math animations
TextToSpeechService      // Voice synthesis
ImageAnalysisService     // Vision AI
```

---

# 5. CACHING ARCHITECTURE

## 5.1 Cache Strategy

### Primary Cache: Redis
```
Host: 127.0.0.1
Port: 6379
Prefix: blinkstudy_cache_
```

### Fallback: File Cache
```
Path: storage/framework/cache/data
```

## 5.2 Cached Data

| Data Type | TTL | Key Pattern |
|-----------|-----|-------------|
| App Config | 1 hour | `app_config` |
| Frontend Config | 30 min | `frontend_config_*` |
| User Session | 24 hours | `user_session_*` |
| Quiz Data | 1 hour | `quiz_*` |
| AI Response | 10 min | `ai_cache_*` |
| Rate Limits | varies | `rate_limit_*` |

## 5.3 Queue System

### Queue Driver: Redis
```
Connection: default
Queue Names: default, high, low
Workers: 3 (Supervisor managed)
```

### Queued Jobs
```
ProcessWhiteboardVideo
SendNotification
GenerateQuizAsync
CleanupOldChats
```

---

# 6. REVENUE MODEL

## 6.1 Subscription Plans

### Free Plan
| Feature | Limit |
|---------|-------|
| AI Chats | 10/day |
| Image Scans | 3/day |
| Quizzes | 5/day |
| Whiteboard Videos | 1/day |
| Study Battles | 2/day |
| **Ads** | Yes (Banner + Interstitial) |

### Basic Plan - ₹99/month
| Feature | Limit |
|---------|-------|
| AI Chats | 50/day |
| Image Scans | 20/day |
| Quizzes | 20/day |
| Whiteboard Videos | 5/day |
| Study Battles | 10/day |
| **Ads** | No |

### Pro Plan - ₹199/month
| Feature | Limit |
|---------|-------|
| AI Chats | 200/day |
| Image Scans | 50/day |
| Quizzes | 50/day |
| Whiteboard Videos | 20/day |
| Study Battles | 30/day |
| **Ads** | No |

### Premium Plan - ₹499/month
| Feature | Limit |
|---------|-------|
| AI Chats | Unlimited |
| Image Scans | Unlimited |
| Quizzes | Unlimited |
| Whiteboard Videos | 50/day |
| Study Battles | Unlimited |
| **Priority AI** | Yes |
| **Ads** | No |

## 6.2 Payment Gateways

| Gateway | Status | Features |
|---------|--------|----------|
| **Razorpay** | Active | UPI, Cards, Netbanking, Wallets |
| **Cashfree** | Active | UPI, Cards |
| **PhonePe** | Active | UPI only |

## 6.3 Ad Monetization (AdMob)

| Ad Type | Unit ID (Android) | Placement |
|---------|-------------------|-----------|
| Banner | `ca-app-pub-4879680310084425/7424299649` | Bottom of screens |
| Interstitial | `ca-app-pub-4879680310084425/4648925392` | Between features |
| Rewarded | `ca-app-pub-4879680310084425/XXXXXXXXXX` | Extra credits |
| Native | `ca-app-pub-4879680310084425/9591254716` | In-feed ads |

**Note**: Ads only shown to Free plan users.

## 6.4 Cost Structure

### Fixed Costs (Monthly Estimates)
| Item | Cost |
|------|------|
| Server Hosting | ₹2,000-5,000 |
| Domain + SSL | ₹100 |
| Redis/Cache | Included |
| Total Fixed | ~₹2,100-5,100/month |

### Variable Costs (Per User)
| Service | Cost per 1000 requests |
|---------|------------------------|
| Gemini API | Free (generous limits) |
| OpenAI GPT-4 | $15-30 |
| Claude | $15-25 |
| TTS (Google) | $4-16 |
| Manim Videos | Compute only |

### Revenue Streams
1. **Subscriptions**: ₹99-499/month per paid user
2. **Ads**: ₹0.10-0.50 per ad impression (Free users)
3. **In-app Credits**: Future implementation

---

# 7. WEBSITE STRUCTURE

## 7.1 Public Pages
```
/                   → Landing Page (Hero + Features + Pricing)
/about              → About Us
/support            → Support & FAQ
/privacy-policy     → Privacy Policy
/terms              → Terms of Service
/contact            → Contact Form
/plans              → Pricing Plans
/login              → Unified Login (OTP-based)
```

## 7.2 User Dashboard
```
/dashboard          → Main Dashboard
/chat               → AI Chat Interface
/quiz               → Quiz Module
/exam-prep          → Exam Preparation
/history            → Chat/Quiz History
/profile            → User Profile
/settings           → Account Settings
/subscription       → Manage Subscription
```

## 7.3 Admin Panel
```
/admin              → Admin Dashboard
/admin/users        → User Management
/admin/subscriptions → Subscription Management
/admin/payments     → Payment History
/admin/ai-models    → AI Provider Config
/admin/settings     → System Settings
/admin/analytics    → Usage Analytics
/admin/content      → Content Management
```

---

# 8. MOBILE APP STRUCTURE

## 8.1 Navigation Structure

### Tab Navigation
```
├── Home (Dashboard)
├── Chat (AI Doubt Solver)
├── Quiz (Assessments)
├── Scan (Camera/Image)
└── Profile (Settings)
```

### Stack Screens
```
├── Auth
│   ├── Login
│   ├── OTP Verification
│   └── Onboarding
├── Chat
│   ├── Chat List
│   ├── Chat Detail
│   └── Image Preview
├── Quiz
│   ├── Topic Selection
│   ├── Quiz Taking
│   └── Results
├── Exam Prep
│   ├── Exam Selection
│   └── Practice Test
├── Study Battle
│   ├── Lobby
│   ├── Battle Room
│   └── Leaderboard
└── Settings
    ├── Profile Edit
    ├── Subscription
    ├── Notifications
    └── About
```

## 8.2 Key Screens

| Screen | Description |
|--------|-------------|
| `HomeScreen` | Dashboard with quick actions |
| `ChatScreen` | AI conversation interface |
| `QuizScreen` | Quiz taking with timer |
| `ScanScreen` | Camera/gallery image capture |
| `ProfileScreen` | User profile & settings |
| `PlansScreen` | Subscription plans |
| `HistoryScreen` | Past chats and quizzes |
| `BattleScreen` | Study battle lobby |

## 8.3 App Configuration

### Expo Configuration (app.json)
```json
{
  "name": "BlinkStudy",
  "slug": "mindory-app",
  "version": "1.2.1",
  "android": {
    "package": "com.blinkstudy.app",
    "versionCode": 21
  },
  "ios": {
    "bundleIdentifier": "com.blinkstudy.app"
  }
}
```

### Permissions
- Camera (Note scanning)
- Microphone (Voice input)
- Photo Library (Image upload)
- Internet (API calls)
- Notifications (Push alerts)

---

# 9. SECURITY MEASURES

## 9.1 Authentication
- OTP-based login (no passwords)
- Laravel Sanctum tokens
- Token refresh mechanism
- Session timeout: 30 days

## 9.2 API Security
- Rate limiting per endpoint
- Input validation
- SQL injection prevention
- XSS protection
- CSRF tokens (web)

## 9.3 Data Protection
- HTTPS everywhere
- Encrypted storage
- Secure API keys (env)
- No sensitive data in logs

---

# 10. DEPLOYMENT

## 10.1 Server Configuration
```
Provider: VPS (IP: 84.247.138.164)
OS: Ubuntu 22.04 LTS
Web Server: Nginx
PHP: 8.2-FPM
Database: MySQL 8.0
Cache: Redis 7.x
SSL: Let's Encrypt (Cloudflare)
```

## 10.2 Directory Structure
```
/www/wwwroot/blinkstudy.in/
├── app/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
└── vendor/
```

## 10.3 Mobile App Distribution
- **Android**: Google Play Store
- **iOS**: Apple App Store (planned)
- **Updates**: Expo OTA Updates

---

# 11. BRAND GUIDELINES

## 11.1 Voice & Tone
- **Friendly**: Approachable, supportive
- **Educational**: Clear, informative
- **Encouraging**: Motivating, positive
- **Simple**: Easy to understand

## 11.2 Visual Style
- **Clean**: Minimal, uncluttered
- **Modern**: Contemporary design
- **Consistent**: Unified across platforms
- **Accessible**: High contrast, readable

## 11.3 Do's and Don'ts

### Do's
- Use primary teal for CTAs
- Maintain consistent spacing
- Use Outfit font family
- Keep interfaces clean
- Use icons for clarity

### Don'ts
- Don't use more than 3 colors per screen
- Don't use fonts below 12px
- Don't crowd interfaces
- Don't use harsh color transitions
- Don't skip loading states

---

# 12. CONTACT & SUPPORT

## 12.1 Support Channels
- **Email**: support@blinkstudy.in
- **Website**: https://blinkstudy.in/support
- **In-app**: Chat support

## 12.2 Social Media
- (To be added)

---

**Document Version**: 1.0
**Last Updated**: February 2026
**Prepared for**: BlinkStudy Internal Use

---

*This brand book is confidential and intended for internal use only.*

# BlinkStudy - CLAUDE.md
## Project Instructions & Protected Systems

---

## ⚠️ CRITICAL RULE - READ FIRST

**Jab bhi koi change karo, SIRF wahi file touch karo jo task mein explicitly mention hai.**
Agar ek button fix karna hai, toh sirf woh button ki file - poora component mat rewrite karo.

---

## 🔴 DO NOT TOUCH - Protected Systems

### 1. Caching System (HANDS OFF — YE POORA FOLDER MAT CHHEDO)

**Ye exact files BILKUL mat chhedo:**
- `app/Http/Middleware/SmartCacheMiddleware.php` ← 4-layer cache system
- `app/Services/Cache/SmartCacheService.php` ← Cache lookup + store logic
- `app/Services/Cache/ConversationGuard.php` ← Active conversation check (Layer 1)
- `app/Services/Cache/MessageClassifier.php` ← Message type + word list (Layer 2)

**Ye cheezein mat badlo:**
- `classify()` method ka word list — "Yes/No/Haan" wala bug wapas aayega
- Cache key generation logic — conversation mixing bug aayega
- Layer 1 ka 10-minute active conversation window
- Layer 4 ka response store guard skip patterns
- `isFirstMessage()` logic
- `extractQuestion()` field names
- TTL values — bina poochhe mat badlo

> **Why:** Ye system API costs 40-60% reduce karta hai. "Yes" bhejo toh kisi aur ka cached response aata tha — YE BUG EK BAAR HO CHUKA HAI. Dobara nahi hona chahiye.

### 2. Authentication & OTP Flow
- OTP send/verify endpoints mat badlo
- `AuthController.php` mein logic mat chhedo jab tak auth-related task na ho
- Phone number validation rules mat badlo
- Token generation/validation mat chhedo

### 3. Payment & Subscription System
- Razorpay integration files mat chhedo
- Subscription tier logic (Free/Lite/Pro/Ultimate) mat badlo
- `SubscriptionController.php` mat chhedo
- Webhook handlers mat chhedo

### 4. AI Provider Integration (HANDS OFF)

**Exact file:**
- `app/Services/AIService.php` ← PRIMARY PROTECTED FILE

**Mat badlo:**
- Provider selection logic (Gemini → OpenAI fallover order)
- `selectModel()` method — model switching logic
- `callWithFailover()` method — failover chain
- API call headers aur request format
- Token counting / limiting functions
- System prompt text (directly AIService mein jo hai)
- Response parsing logic
- Error handling / retry logic

> **Why:** AIService ek baar UI update ki wajah se break ho chuka hai jab kisi ne indirectly is file ko touch kiya. Server Error tab aaya tha. Dobara nahi.

### 5. UI Theme & Design System
- Primary color: `#0D7377` (teal/green)
- Font sizes, spacing system mat badlo
- Common components (Button, Card, Modal) ka design mat chhedo
- `theme.js` / `colors.js` / `styles.js` mat badlo

### 6. Database Schema & Migrations
- Existing tables ki columns mat rename/delete karo
- `users` table — mat chhedo (auth poori is pe dependent hai)
- `subscriptions` / `user_plans` table — mat chhedo (payment data hai)
- `conversations` / `messages` table — mat chhedo (caching is pe based hai)
- Foreign key constraints mat hatao
- Naya migration banao, purana edit nahi

### 7. API Keys & Environment Variables
- `.env` file mat chhedo kabhi bhi
- `config/services.php` mat badlo
- Hardcoded API keys kabhi bhi code mein mat daalo
- `.env.example` update karo agar naya variable add karo

### 8. User Data & Privacy
- User ka phone number, name, email processing logic mat badlo
- Student ke exam data (marks, progress) ka structure mat badlo
- `getUserData()` ya similar functions mat chhedo
- Data delete/wipe functions extremely carefully handle karo

### 9. Subscription & Access Control
- Free vs Paid feature gating logic mat badlo (users bypass kar lenge)
- `checkSubscription()` / `hasAccess()` middleware mat chhedo
- Trial period logic mat badlo
- Plan expiry check mat chhedo

### 10. Anti-Cheat & Exam Integrity
- Quiz answer validation logic mat chhedo
- Timer logic mat badlo
- Question shuffling seed mat badlo
- Score calculation functions mat chhedo

### 11. Topper Connect System
- Payment split logic mat chhedo (topper ko milne wale paise)
- Chat session creation/ending logic mat chhedo
- Rating/review system mat badlo

### 12. Push Notifications
- FCM token storage/retrieval mat chhedo
- Notification trigger logic mat badlo (double notifications aayenge)
- `NotificationService.php` mat chhedo

### 13. File Upload & Storage
- Scan & Solve ke liye image upload path mat badlo
- Storage disk configuration mat badlo
- File size/type validation mat hatao

---

## 🟡 CHANGE WITH CAUTION

### Database Migrations
- Naya migration banao, existing mat badlo
- Foreign keys carefully check karo
- Rollback plan socho pehle

### API Response Structure
- Existing response format mat badlo (app crash hoga)
- Naye fields ADD kar sakte ho, existing mat hatao
- Error codes consistent rakho

### React Native Navigation
- Navigation stack structure mat badlo
- Screen names mat rename karo (deeplinks tutenge)

---

## 🟢 SAFE TO CHANGE

- UI text, labels, messages
- New screens/components add karna
- New API endpoints add karna (existing touch na karo)
- CSS/StyleSheet tweaks on specific screens
- New utility functions add karna
- Console.log cleanup

---

## 📋 Before Making ANY Change - Checklist

```
[ ] Kya main sirf wahi file touch kar raha hoon jo task mein bola gaya?
[ ] Kya main koi existing function rename/delete kar raha hoon? (agar haan - RUKO)
[ ] Kya is change se caching system affect hoga?
[ ] Kya is change se auth/OTP flow affect hoga?
[ ] Kya API response format badal raha hai?
[ ] Kya database migration existing columns affect kar raha hai?
[ ] Kya subscription/access control logic affect ho raha hai?
[ ] Kya .env ya API keys kuch expose ho rahe hain?
[ ] Kya user ka personal data (phone, marks, progress) affect ho raha hai?
[ ] Kya maine pehle wali working code backup/comment kiya?
```

---

## 🏗️ Project Architecture

### Backend (Laravel)
```
app/
  Http/
    Controllers/
      AuthController.php          ← OTP/Login [PROTECTED]
      SubscriptionController.php  ← Plans [PROTECTED]  
      AIController.php            ← AI calls [PROTECTED]
    Middleware/
      SmartCacheMiddleware.php    ← Cache [PROTECTED]
  Services/
    CacheService.php              ← Cache logic [PROTECTED]
    AIService.php                 ← AI provider [PROTECTED]
```

### Frontend (React Native)
```
src/
  screens/         ← Screen components
  components/      ← Reusable UI
  navigation/      ← Nav structure [PROTECTED]
  theme/           ← Design tokens [PROTECTED]
  services/        ← API calls
  utils/           ← Helper functions
```

---

## 🔴 AI Chat System - Extra Protection

**Ye system ek baar toot chuka hai UI update ke wajah se. Double protection.**

### Frontend (React Native) - Chat Screen
- `src/screens/ChatScreen.js` — sirf UI changes allowed, koi bhi API call logic mat chhedo
- `src/services/chatService.js` (ya similar) — HANDS OFF
- Request body format mat badlo: `{ message, conversation_id, user_id }` exact structure rakho
- Headers mat badlo: `Authorization: Bearer <token>` aur `Accept: application/json` required hai
- Websocket/polling logic mat chhedo

### Backend (Laravel) - Chat Endpoint
- `routes/api.php` mein chat route mat badlo
- `AIController@chat` (ya similar method) ka response format mat badlo
- `app/Http/Middleware/` mein koi bhi naya middleware chat routes pe mat lagao bina test kiye
- Error response format consistent rakho: `{ success: false, message: "..." }`

### ⚠️ Known Breakage Pattern
> UI update kiya → ChatScreen.js rewrite hua → API call ka request format/headers silently badal gaye → "Server Error" aane laga

**Ye EXACT pattern hua hai. Agli baar ChatScreen UI update karte waqt:**
1. Pehle existing API call code copy karke alag rakh lo
2. Sirf JSX/StyleSheet badlo
3. API call wala code WAPAS PASTE karo - exact same

---

## 🚨 Common Mistakes Claude Should AVOID

1. **UI update maanga → caching middleware bhi change kar diya** ❌
2. **Button fix maanga → poora component rewrite kar diya** ❌  
3. **New feature maanga → existing API endpoint ka response format badal diya** ❌
4. **Style fix maanga → theme.js ke global variables badal diye** ❌
5. **Error handling improve karna tha → try-catch remove kar diya** ❌
6. **ChatScreen ka UI update maanga → API call headers/body silently badal diye → Server Error** ❌
7. **Koi naya middleware add kiya → accidentally chat/AI routes bhi block ho gaye** ❌

---

## 📝 How to Ask Claude for Changes (Template)

Jab Rajnish kuch change karna chahta hai, is format mein puchho:

```
Task: [Kya karna hai - specific]
File: [Konsi specific file]
Do NOT touch: [Jo protect karna hai]
Context: [Koi relevant background]
```

**Example:**
```
Task: Login screen pe error message ka color red se orange karna hai
File: src/screens/LoginScreen.js - sirf error text style
Do NOT touch: OTP logic, API calls, navigation
Context: Just visual change hai
```

---

## 🔧 Environment Info

- **Backend:** Laravel (PHP)
- **Frontend:** React Native
- **Database:** MySQL
- **Cache:** Redis/Laravel Cache
- **Payments:** Razorpay
- **AI Providers:** Gemini (primary), OpenAI, Claude (fallback)
- **Auth:** OTP-based (phone number)

---

## 📌 Current Active Features (Don't Break These)

- [ ] OTP Login/Register
- [ ] AI Chat (with caching)
- [ ] Scan & Solve
- [ ] Quiz Generation
- [ ] Subscription Management
- [ ] Topper Connect (peer chat)
- [ ] UPSC/JEE/NEET/CBSE content

---

*Last Updated: February 2026*
*Project: BlinkStudy (formerly Mindory)*
*Developer: Rajnish*
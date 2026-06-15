# BlinkStudy Flutter App (v1.4.0)

AI-powered competitive exam preparation app — Flutter rewrite matching the new UI/UX design.

## Package & Signing

| Property | Value |
|----------|-------|
| Package ID | `com.blinkstudy.app` |
| Version | 1.4.0 (versionCode 40) |
| Keystore | `android/app/blinkstudy-release.keystore` |
| Alias | `blinkstudy` |

Uses the **same keystore** as the live Play Store app for seamless updates.

## PRD Core Features Implemented

1. **Home Dashboard** — streak, XP/levels, daily plan, progress ring, quick actions
2. **AI Tutor Chat** — BlinkAI chat with API integration + markdown
3. **Scan & Solve** — camera/gallery OCR via image upload to backend
4. **AI Quiz Engine** — topic quizzes with instant feedback
5. **Study Battles** — 1v1 live battle UI (backend routes pending sync)
6. **Exam Prep Hub** — syllabus progress, weak topics, mock tests
7. **Revision System** — flashcards, formula sheets, saved notes
8. **Performance Analytics** — charts, accuracy, weak topics
9. **Profile & Monetization** — plan upgrade, logout

## Auth Flow

1. **Splash** → checks saved token
2. **Login** → mobile number (+91) → OTP via `/api/login/send-otp`
3. **Verify** → 4-digit OTP via `/api/login/verify-otp` → Sanctum token saved
4. **Main app** → all API calls use Bearer token

## Setup

### Prerequisites
- Flutter SDK 3.16+ ([install](https://docs.flutter.dev/get-started/install))
- Android Studio / JDK 17

### Install & Run

```bash
cd "updated App"
flutter pub get
flutter run
```

### Build Release APK (Play Store Update)

```bash
flutter build apk --release
# Output: build/app/outputs/flutter-apk/app-release.apk
```

### Build Release AAB (Recommended for Play Store)

```bash
flutter build appbundle --release
# Output: build/app/outputs/bundle/release/app-release.aab
```

## UI Design

Matches the provided mockup:
- Primary purple `#705CF6`
- Rounded cards (20px radius)
- Elevated center Scan button in bottom nav
- 5-tab navigation: Home, Progress, Scan, Battles, Profile

## Project Structure

```
lib/
├── core/theme/          # Colors, theme
├── core/constants/      # API URLs, routes
├── models/              # Data models
├── services/            # API service (Dio)
├── providers/           # Riverpod state
├── screens/             # All feature screens
└── widgets/             # Reusable UI components
```

## Notes

- Update `android/local.properties` with your Flutter SDK path
- Study Battle API routes may need to be registered on backend (`StudyBattleController`)
- For iOS builds, add `ios/` folder via `flutter create . --platforms=ios`

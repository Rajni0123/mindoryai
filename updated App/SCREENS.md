# BlinkStudy — Full App Screen Map

## Auth Flow
| Screen | Route | File |
|--------|-------|------|
| Splash | `/` | `screens/auth/splash_screen.dart` |
| Login (Mobile OTP) | `/login` | `screens/auth/login_screen.dart` |

## Main Shell (Bottom Nav)
| Tab | Screen | File |
|-----|--------|------|
| Home | Dashboard | `screens/home/home_screen.dart` |
| Progress | Analytics | `screens/performance/performance_screen.dart` |
| Scan | OCR Solver | `screens/scan_solve/scan_solve_screen.dart` |
| Battles | Live Battles | `screens/battles/battles_screen.dart` |
| Profile | User Profile | `screens/profile/profile_screen.dart` |

## Feature Screens (All Wired)
| Screen | Route | Navigation From |
|--------|-------|-----------------|
| AI Tutor | `/ai-tutor` | Home quick action, Drawer |
| Quiz Topics | `/quiz-topics` | Home, Drawer, Revision |
| Quiz | `/quiz` | Topic selection |
| Scan & Solve | `/scan-solve` | Home, Drawer, Bottom nav |
| Exam Prep | `/exam-prep` | Home continue learning, Drawer |
| Mock Test | `/mock-test` | Exam Prep |
| Performance | `/performance` | Home analytics, Drawer, Profile |
| Study Battles | `/battles` | Home, Drawer, Bottom nav |
| Battle Lobby | `/battle-lobby` | Battles create/join |
| Revision | `/revision` | Home, Drawer |
| Flashcards | `/flashcards` | Revision |
| Saved Notes | `/saved-notes` | Revision |
| Daily Challenge | `/daily-challenge` | Home card, Drawer, Profile |
| Topper Connect | `/topper-connect` | Drawer, Profile |
| Notifications | `/notifications` | Home bell icon, Drawer, Profile |
| Plans | `/plans` | Drawer, Profile upgrade |
| Settings | `/settings` | Profile gear icon, Drawer |

## Navigation
- **Drawer**: Hamburger menu on Home → all features
- **Bottom Nav**: Home | Progress | Scan | Battles | Profile
- **Router**: `lib/core/router/app_router.dart`

## Build
```bash
cd "updated App"
flutter pub get
flutter run
flutter build appbundle --release
```

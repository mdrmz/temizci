# Temizci Burada Mobile

Flutter shell app for Android and iOS. The app opens the live Temizci Burada
site through the Cloudflare domain:

```text
https://temizciburada.com/
```

The PHP website and APIs stay on the server at:

```text
/var/www/html/Temizlik_Burda
```

## Useful Commands

```sh
/Users/mehmetdurmaz/development/flutter/bin/flutter pub get
/Users/mehmetdurmaz/development/flutter/bin/flutter analyze
/Users/mehmetdurmaz/development/flutter/bin/flutter build apk --debug
export PATH="$HOME/.gem/ruby/2.6.0/bin:$PATH"
export RUBYOPT="-rlogger"
/Users/mehmetdurmaz/development/flutter/bin/flutter build ios --debug --no-codesign
```

Android SDK is expected at:

```text
~/Library/Android/sdk
```

The local Android toolchain uses Temurin JDK 17:

```text
~/.jdks/temurin-17.jdk/Contents/Home
```

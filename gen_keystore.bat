@echo off
set JAVA_HOME="C:\Program Files\Android\Android Studio\jbr"
set PATH=%JAVA_HOME%\bin;%PATH%

if "%KEYSTORE_PASSWORD%"=="" set /p KEYSTORE_PASSWORD=Masukkan password keystore:

keytool -genkeypair -v -keystore "C:\laragon\www\nanangpx\NP-Analytics\android-keystore\np-analytics.keystore" -storepass "%KEYSTORE_PASSWORD%" -keypass "%KEYSTORE_PASSWORD%" -alias npanalytics -keyalg RSA -keysize 2048 -validity 10000 -dname "CN=NP Analytics, O=NP Analytics, C=ID"

SANADK Device Manager — Deployment & Setup
=========================================

هدف هذا الملف: إرشادك لتشغيل جسر الأجهزة (BLE bridge) فعليًا بحيث يرسل بيانات أجهزة Polar/HR عبر REST إلى تطبيق Laravel المستضاف (Laravel Cloud).

ملاحظات سريعة
- لا يمكن تشغيل الوصول إلى بلوتوث جهاز فعلي على خادم Laravel Cloud لأن الخادم لا يملك جهاز بلوتوث متصل.
- الحل الطبيعي: شغّل `device-manager` على جهاز محلي (Raspberry Pi أو خادم Linux على الشبكة) والذي يتصل فعليًا بالأجهزة ثم يرسل البيانات إلى REST API المستضاف.

خيارات النشر الموصى بها
1) Linux (موصى به — Raspberry Pi أو Ubuntu VM)
2) WSL/Windows مع Visual Studio Build Tools (بديل إن لم يتوفر Linux)

المتطلبات (Linux recommended)
- Node.js 18.x
- build-essential, python3 (لإمكانية تجميع بعض الحزم، في حالة استخدام noble)
- BlueZ (Bluetooth stack) وتهيئة البلوتوث على الجهاز

إعداد سريع على Ubuntu/Debian (مثال)
---------------------------------
1. تثبيت متطلبات النظام:

```bash
sudo apt update
sudo apt install -y build-essential python3 curl git libusb-1.0-0-dev bluetooth bluez
# تثبيت Node.js 18
curl -sL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

2. استنساخ المشروع أو انسخ مجلد `services/device-manager` إلى المضيف

3. إعداد متغيرات البيئة في `services/device-manager/.env` (مهم):

```
SANADK_SERVER_URL=http://your-socket-server:6000   # إن كنت تستخدم socket.io
SANADK_REST_ENDPOINT=https://your-laravel-host.example.com/api/devices
SANADK_API_TOKEN=tk_...                              # يجب أن يطابق token في Laravel .env SANADK_API_TOKEN
BLE_NAME_FILTER=Polar
DEVICE_DEFAULT_NAME=polar-h9
```

4. تثبيت تبعيات Node:

```bash
cd services/device-manager
npm ci
```

5. تشغيل الجسر:

```bash
# بشكل مباشر
node index.js

# أو تفعيل خدمة systemd (المسارات في sanadk-device-manager.service افتراضية — عدّلها حسب مكان العمل)
sudo cp systemd/sanadk-device-manager.service /etc/systemd/system/sanadk-device-manager.service
sudo systemctl daemon-reload
sudo systemctl enable --now sanadk-device-manager.service
sudo journalctl -u sanadk-device-manager -f
```

ملاحظات عن Windows
-------------------
- إذا رغبت تشغيله على Windows، يجب تثبيت "Build Tools for Visual Studio" مع مكون C++ ثم تشغيل `npm ci`.
- بديل عملي: استخدم WSL2 + Ubuntu (ولكن BLE قد لا يعمل بشكل جيد داخل WSL بسبب قيود تمرير أجهزة البلوتوث).

توصية عملية لاستقرار حقيقي
---------------------------
- استخدم Raspberry Pi 4 أو خادم Linux صغير متصل بالبلوتوث كجسر. هذا يبقي الأجهزة قريبة ويسمح ببقائها تعمل 24/7.
- اجعل `device-manager` يُرسل إلى REST endpoint المستضاف (`SANADK_REST_ENDPOINT`) بدلاً من الاعتماد على socket.io على الخادم المستضاف (أسهل للتوافق مع Laravel Cloud).

تكوين Laravel Cloud
--------------------
- في `.env` للتطبيق المستضاف (Laravel Cloud)، اضبط:

```
SANADK_INCOMING_TOKEN=the_same_token_used_in_device_manager
```

- تأكد أن `routes/api.php` يقبل POST على `/api/devices/{device}/data` ويمكنه التحقق من هيدر `Authorization: Bearer <SANADK_INCOMING_TOKEN>` أو تحقق داخل التابع.

الخطوة التالية التي أستطيع تنفيذها الآن
-------------------------------------
- أ: أعدل `services/device-manager/index.js` ليستخدم افتراضيًا `SANADK_REST_ENDPOINT` إلى `https://sanadk-main-2rievw.laravel.cloud/api/devices` واحتياطات المصادقة.
- ب: أضبط ملف systemd في المكان المناسب وأتأكد أن التعليمات جاهزة للنشر.

اختر (A) أو (B) أو اطلب إرشادًا خطوة-بخطوة على جهازك، وسأكمل التنفيذ فورًا.
# SANADK Device Manager (Local BLE Bridge)

This service runs on a machine that has a Bluetooth adapter (Linux/Raspberry Pi or compatible Windows with supported drivers) and connects to Polar H9 devices, forwards heart-rate data to the SANADK backend via Socket.IO and REST.

Quick start

1. Copy `.env.example` to `.env` and set `SANADK_SERVER_URL` and `SANADK_REST_ENDPOINT`.
2. Install dependencies:

```bash
npm install
```

3. Run the service:

```bash
npm start
```

Systemd (recommended on Linux): create a unit file (`/etc/systemd/system/sanadk-device-manager.service`) and enable it. See `systemd/sanadk-device-manager.service` as example.

Notes
- This implementation listens for the standard BLE Heart Rate service (0x180D) and characteristic 0x2A37.
- For other device types (ECG/EEG/EMG) you will need device-specific GATT handling or a vendor SDK.

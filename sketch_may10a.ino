#include <driver/i2s.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <math.h>

#include <WiFi.h>
#include <HTTPClient.h> 

// ================= OLED =================
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// ================= PIN INMP441 =================
#define I2S_WS   15
#define I2S_SD   32
#define I2S_SCK  14

#define I2S_PORT I2S_NUM_0

// ================= WIFI =================
const char* ssid = "Fawwaz";
const char* password = "salingberbagi";

String serverName = "http://10.47.245.132/monitoring-kebisingan/simpan.php";

// ================= VARIABLE =================
float dBFiltered = 0;
String status = "";
unsigned long lastSendTime = 0;

// ================= I2S SETUP =================
void setupI2S() {

  const i2s_config_t i2s_config = {
    .mode = (i2s_mode_t)(I2S_MODE_MASTER | I2S_MODE_RX),

    // Sample rate cukup untuk monitoring kebisingan
    .sample_rate = 16000,

    .bits_per_sample = I2S_BITS_PER_SAMPLE_32BIT,

    .channel_format = I2S_CHANNEL_FMT_ONLY_LEFT,

    .communication_format = I2S_COMM_FORMAT_STAND_I2S,

    .intr_alloc_flags = ESP_INTR_FLAG_LEVEL1,

    // Buffer kecil agar lebih responsif
    .dma_buf_count = 4,
    .dma_buf_len = 64,

    .use_apll = false,
    .tx_desc_auto_clear = false,
    .fixed_mclk = 0
  };

  const i2s_pin_config_t pin_config = {
    .bck_io_num = I2S_SCK,
    .ws_io_num = I2S_WS,
    .data_out_num = I2S_PIN_NO_CHANGE,
    .data_in_num = I2S_SD
  };

  i2s_driver_install(I2S_PORT, &i2s_config, 0, NULL);
  i2s_set_pin(I2S_PORT, &pin_config);

  i2s_zero_dma_buffer(I2S_PORT);
}

// ================= SETUP =================
void setup() {

  Serial.begin(115200);

  // ================= WIFI =================
  WiFi.begin(ssid, password);

  Serial.print("Menghubungkan WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.println("WiFi Terhubung!");
  Serial.print("IP ESP32 : ");
  Serial.println(WiFi.localIP());

  // OLED
  Wire.begin(21, 22);

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("OLED gagal");
    while (true);
  }

  display.clearDisplay();
  display.display();

  setupI2S();

  // Splash Screen
  display.setTextSize(1);
  display.setTextColor(WHITE);
  display.setCursor(10, 25);
  display.println("Noise Monitoring");
  display.display();

  delay(1000);
}

// ================= LOOP =================
void loop() {

  int32_t samples[256];
  size_t bytesRead;

  i2s_read(
    I2S_PORT,
    &samples,
    sizeof(samples),
    &bytesRead,
    portMAX_DELAY
  );

  int sampleCount = bytesRead / sizeof(int32_t);

  double sum = 0;

  // ================= RMS =================
  for (int i = 0; i < sampleCount; i++) {

    float sample = samples[i] >> 8;

    sample = (sample / 8388608.0) * 4.0;

    sum += sample * sample;
  }

  float rms = sqrt(sum / sampleCount);

  // ================= HITUNG dB =================
  float dB = 0;

  if (rms > 0.00005) {

    dB = 20 * log10(rms / 0.00002);

    // Kalibrasi awal
    dB -= 25;

    if (dB < 0)
      dB = 0;
  }

// ================= RESPONSIVE SMOOTHING =================
dBFiltered = (0.4 * dBFiltered) + (0.6 * dB);

// ================= STATUS =================
if (dBFiltered < 45)
{
    status = "Sunyi";
}
else if (dBFiltered <= 55)
{
    status = "Kondusif";
}
else
{
    status = "Bising";
}

// ================= SERIAL MONITOR =================
Serial.print("Noise: ");
Serial.print(dBFiltered, 1);
Serial.print(" dB | Status: ");
Serial.println(status);

// ================= KIRIM KE DATABASE =================
if (millis() - lastSendTime >= 5000)
{
    if (WiFi.status() == WL_CONNECTED)
    {
        HTTPClient http;

        String url =
        "http://10.47.245.132/monitoring-kebisingan/simpan.php?db="
        + String(dBFiltered, 1)
        + "&status=" + status;

        http.begin(url);

        int httpCode = http.GET();

        Serial.print("HTTP Code: ");
        Serial.println(httpCode);

        http.end();
    }

    lastSendTime = millis();
}

  // ================= OLED =================
  display.clearDisplay();

  // Judul
  display.setTextSize(1);
  display.setTextColor(WHITE);
  display.setCursor(0, 0);
  display.println("Noise Level");

  // Nilai dB
  display.setTextSize(2);
  display.setCursor(0, 14);
  display.print(dBFiltered, 1);
  display.println(" dB");

  // ================= STATUS =================
  display.setTextSize(1);
  display.setCursor(0, 42);
  display.println("Status: " + status);

  // ================= BAR =================
  // Fokus pada rentang penelitian 40-60 dB
  int barWidth = map((int)dBFiltered, 40, 60, 0, 120);

  barWidth = constrain(barWidth, 0, 120);

  display.drawRect(0, 54, 120, 8, WHITE);
  display.fillRect(0, 54, barWidth, 8, WHITE);

  display.display();

  delay(20);
}
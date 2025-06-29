#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Adafruit_NeoPixel.h>

// === DEFINIZIONI ===
#define PIN_LED 21
#define NUM_LEDS 11    // 9 esercizio + 2 mani
#define BUZZER_PIN 22
#define NUM_BEEPS 6    // Numero di beep

const int buttonPins[9] = {18, 19, 5, 16, 4, 13, 14, 27, 26};
const int ledMapping[9] = {0, 3, 4, 7, 6, 5, 10, 9, 8};

Adafruit_NeoPixel strip(NUM_LEDS, PIN_LED, NEO_GRB + NEO_KHZ800);

const char* ssid = "Giulietta";
const char* password = "giulia2002";
const char* serverUrl = "http://172.20.10.4:8888/alzheimer-app.old/getExercise2.php";

struct Step {
  int buttonIndex;
  String hand;
};

std::vector<Step> sequence;
bool sequenceActive = false;
int currentStep = 0;
int repetitions = 0;
int totalSteps = 0;
int errori = 0;
int corretti = 0;

// === FUNZIONI ===

void connectWiFi() {
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 20) {
    delay(500);
    Serial.print(".");
    retries++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWiFi connection failed!");
  }
}

void fetchSequence() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    int httpCode = http.GET();
    if (httpCode > 0) {
      String payload = http.getString();
      Serial.println("Received: " + payload);
      parseSequence(payload);
    } else {
      Serial.println("HTTP Error: " + String(httpCode));
    }
    http.end();
  }
}

void parseSequence(String jsonData) {
  sequence.clear();
  currentStep = 0;
  repetitions = 0;
  errori = 0;
  corretti = 0;

  DynamicJsonDocument doc(2048);
  DeserializationError error = deserializeJson(doc, jsonData);
  if (!error) {
    repetitions = doc["r"].as<int>();
    JsonArray seqArray = doc["seq"].as<JsonArray>();
    for (JsonArray step : seqArray) {
      Step s;
      s.buttonIndex = step[0].as<int>() - 1; // PHP parte da 1, Arduino da 0
      s.hand = step[1].as<String>();
      sequence.push_back(s);
    }
    totalSteps = sequence.size();
    if (totalSteps > 0) {
      sequenceActive = true;
      segnaleInizioFine(false);  // Segnale di inizio
      activateStep(0);
    }
  } else {
    Serial.println("JSON parse error");
  }
}

void activateStep(int index) {
  if (index < totalSteps) {
    Step s = sequence[index];
    uint32_t color = (s.hand == "s") ? strip.Color(255, 0, 0) : strip.Color(0, 0, 255);
    strip.clear();
    strip.setPixelColor(1, strip.Color(255, 0, 0)); // Mano destra
    strip.setPixelColor(2, strip.Color(0, 0, 255)); // Mano sinistra
    strip.setPixelColor(ledMapping[s.buttonIndex], color);
    strip.show();
    Serial.print("LED ");
    Serial.print(s.buttonIndex + 1);
    Serial.print(" (");
    Serial.print(s.hand);
    Serial.println(") acceso. Attendere pressione pulsante.");
  }
}

void inviaRisultato() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin("http://172.20.10.4:8888/alzheimer-app.old/save_result.php");
    http.addHeader("Content-Type", "application/json");
    String paziente = "Carmelo Ugolini";
    String json = "{\"paziente\":\"" + paziente + "\",\"corretti\":" + String(corretti) + ",\"errori\":" + String(errori) + "}";
    int httpCode = http.POST(json);
    if (httpCode > 0) {
      Serial.println("Risultato inviato con successo");
    } else {
      Serial.println("Errore nell'invio del risultato");
    }
    http.end();
  }
}

// Segnale inizio/fine: tutti i LED bianchi + buzzer
void segnaleInizioFine(bool finale) {
  // Pulisce eventuali LED accesi prima del segnale
  strip.clear();
  strip.show();

  // Accende tutti i LED di bianco
  for (int i = 0; i < NUM_LEDS; i++) {
    strip.setPixelColor(i, strip.Color(255, 255, 255));
  }
  strip.show();  // Mostra subito i LED bianchi

  // Buzzer NUM_BEEPS volte
  for (int i = 0; i < NUM_BEEPS; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(100);
    digitalWrite(BUZZER_PIN, LOW);
    delay(100);
  }

  // Spegne tutti i LED
  strip.clear();

  if (!finale) {
    // Se siamo all'inizio, riaccendiamo i LED delle mani
    strip.setPixelColor(1, strip.Color(255, 0, 0)); // Mano destra
    strip.setPixelColor(2, strip.Color(0, 0, 255)); // Mano sinistra
  }

  strip.show();
}

void setup() {
  Serial.begin(115200);
  strip.begin();
  strip.show();

  // RIMOSSO: LED delle mani accesi all'avvio
  // Ora i LED delle mani si accenderanno solo dopo il segnale di inizio

  for (int i = 0; i < 9; i++) {
    pinMode(buttonPins[i], INPUT_PULLUP);
  }
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  connectWiFi();
  fetchSequence();
}

void loop() {
  if (sequenceActive && currentStep < totalSteps) {
    int btnIndex = sequence[currentStep].buttonIndex;
    for (int i = 0; i < 9; i++) {
      if (digitalRead(buttonPins[i]) == LOW) {
        delay(100); // debounce
        if (digitalRead(buttonPins[i]) == LOW) {
          if (i == btnIndex) {
            corretti++;
            strip.setPixelColor(ledMapping[i], 0);
            strip.setPixelColor(1, strip.Color(255, 0, 0));
            strip.setPixelColor(2, strip.Color(0, 0, 255));
            strip.show();
            Serial.print("Pulsante ");
            Serial.print(i + 1);
            Serial.println(" corretto. LED spento.");

            digitalWrite(BUZZER_PIN, HIGH);
            delay(100);
            digitalWrite(BUZZER_PIN, LOW);

            delay(500);
            currentStep++;
            if (currentStep < totalSteps) {
              activateStep(currentStep);
            } else {
              repetitions--;
              if (repetitions > 0) {
                currentStep = 0;
                activateStep(currentStep);
              } else {
                sequenceActive = false;
                Serial.print("Sequenza completata! E:");
                Serial.print(errori);
                Serial.print(" C:");
                Serial.println(corretti);
                segnaleInizioFine(true);  // Segnale di fine
                inviaRisultato();
              }
            }
          } else {
            errori++;
            Serial.print("Errore! Hai premuto ");
            Serial.print(i + 1);
            Serial.print(" invece di ");
            Serial.println(btnIndex + 1);
            delay(500);
          }
        }
      }
    }
  }
}
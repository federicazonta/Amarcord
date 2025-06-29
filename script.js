document.addEventListener("DOMContentLoaded", () => {
  const path = window.location.pathname;

  if (path.includes("home.html")) {
    // HOME PAGE: eventuale logica dinamica
    console.log("Homepage");
  }

  if (path.includes("profilo.html")) {
    fetch("getExercise.php")
      .then(res => res.json())
      .then(data => {
        const container = document.querySelector(".storico-box");
        container.innerHTML += data.map(e => `
          <div class="exercise-row">
            <div>x${e.ripetizioni} <code>${e.id}</code><br>
              Tot ${e.sequenza?.split(',').length || 0} ✓ ${e.sequenza}
            </div>
            <div class="exercise-meta">
              <small>${e.giorno}</small>
              <div class="patient-status ${e.stato === 'si' ? 'green' : 'red'}"></div>
            </div>
          </div>
        `).join("");
      })
      .catch(err => console.error("Errore caricamento storico:", err));
  }

  if (path.includes("riepilogo.html")) {
    const data = getExerciseDataFromURL();
    document.getElementById("paziente-nome").textContent = data.paziente;
    document.getElementById("ripetizioni").textContent = data.ripetizioni;
    document.getElementById("sequenza").innerHTML = data.sequenza.map(step => {
      const color = step.endsWith('L') ? 'red' : 'blue';
      return `<span style="color:${color}; font-weight:bold;">${step}</span>`;
    }).join(" ");

    document.querySelector("button.add-btn").addEventListener("click", () => {
      const giorno = document.getElementById('giorno').value;
      const orario = document.getElementById('orario').value;

      if (!giorno || !orario) {
        alert("Seleziona giorno e orario");
        return;
      }

      const esercizio = {
        paziente: data.paziente,
        esercizio: data.sequenza,
        ripetizioni: parseInt(data.ripetizioni),
        giorno,
        orario
      };

      fetch("save_exercise.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(esercizio)
      })
        .then(res => res.json())
        .then(ris => {
          if (ris.status === "ok") {
            alert("Esercizio salvato!");
            window.location.href = "home.html";
          } else {
            alert("Errore: " + ris.message);
          }
        })
        .catch(err => {
          console.error(err);
          alert("Errore durante il salvataggio.");
        });
    });
  }
});

// Per passare i dati via URL
function redirectToRiepilogo(paziente, sequenza, ripetizioni) {
  const params = new URLSearchParams({
    paziente,
    sequenza: sequenza.join(','),
    ripetizioni
  });
  window.location.href = 'riepilogo.html?' + params.toString();
}

// Per leggere i dati in riepilogo.html
function getExerciseDataFromURL() {
  const params = new URLSearchParams(window.location.search);
  return {
    paziente: params.get('paziente') || '',
    sequenza: (params.get('sequenza') || '').split(','),
    ripetizioni: params.get('ripetizioni') || 1
  };
}

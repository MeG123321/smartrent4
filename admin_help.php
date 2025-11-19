<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_role('admin');
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pomoc dla Administratora — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .help-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 20px;
    }
    .help-section {
      background: var(--card);
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 20px;
      border: 1px solid rgba(255,255,255,0.05);
    }
    .help-section h2 {
      color: var(--accent);
      margin-top: 0;
      margin-bottom: 16px;
      font-size: 1.5rem;
    }
    .help-section h3 {
      color: var(--accent-2);
      margin-top: 20px;
      margin-bottom: 12px;
      font-size: 1.2rem;
    }
    .help-section p, .help-section li {
      color: var(--muted);
      line-height: 1.6;
      margin-bottom: 12px;
    }
    .help-section ul, .help-section ol {
      margin-left: 20px;
    }
    .step-list {
      background: rgba(255,255,255,0.02);
      border-left: 3px solid var(--accent);
      padding: 12px 16px;
      margin: 16px 0;
    }
    .feature-box {
      background: rgba(96,165,250,0.08);
      border-radius: 8px;
      padding: 12px;
      margin: 12px 0;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 20px;
      color: var(--accent-2);
      text-decoration: none;
    }
    .back-link:hover {
      color: var(--accent);
    }
  </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main class="container">
  <div class="help-container">
    <a href="admin_panel.php" class="back-link">← Powrót do Panelu Administratora</a>
    
    <h1 style="color: var(--text); margin-bottom: 32px;">📚 Centrum Pomocy dla Administratora</h1>

    <div class="help-section">
      <h2>🎯 Przegląd Panelu Administratora</h2>
      <p>Panel Administratora smartrent to kompleksowe narzędzie do zarządzania platformą wynajmu nieruchomości. Tutaj znajdziesz wszystkie funkcje niezbędne do efektywnego administrowania systemem.</p>
    </div>

    <div class="help-section">
      <h2>🏠 Zarządzanie Nieruchomościami</h2>
      
      <h3>Dodawanie nowych nieruchomości</h3>
      <div class="step-list">
        <ol>
          <li>Przejdź do zakładki "Zarządzanie Nieruchomościami"</li>
          <li>Kliknij przycisk "Dodaj Nieruchomość"</li>
          <li>Wypełnij wszystkie wymagane pola:
            <ul>
              <li><strong>Tytuł</strong> - Atrakcyjna nazwa nieruchomości</li>
              <li><strong>Opis</strong> - Szczegółowy opis lokalu i jego wyposażenia</li>
              <li><strong>Cena</strong> - Cena wynajmu za dzień w PLN</li>
              <li><strong>Miasto</strong> - Lokalizacja nieruchomości</li>
              <li><strong>Zdjęcie</strong> - Wybierz plik graficzny (PNG, JPG, JPEG)</li>
            </ul>
          </li>
          <li>Kliknij "Dodaj Nieruchomość" aby zapisać</li>
        </ol>
      </div>

      <h3>Edycja istniejących nieruchomości</h3>
      <div class="step-list">
        <ol>
          <li>W liście nieruchomości znajdź obiekt do edycji</li>
          <li>Kliknij przycisk "Edytuj" przy wybranej nieruchomości</li>
          <li>Wprowadź zmiany w formularzu</li>
          <li>Zapisz zmiany klikając "Aktualizuj Nieruchomość"</li>
        </ol>
      </div>

      <h3>Usuwanie nieruchomości</h3>
      <p>⚠️ <strong>Uwaga:</strong> Usunięcie nieruchomości jest operacją nieodwracalną!</p>
      <div class="step-list">
        <ol>
          <li>Znajdź nieruchomość na liście</li>
          <li>Kliknij przycisk "Usuń"</li>
          <li>Potwierdź operację w wyświetlonym oknie dialogowym</li>
        </ol>
      </div>
    </div>

    <div class="help-section">
      <h2>👥 Zarządzanie Użytkownikami</h2>
      
      <h3>Przeglądanie użytkowników</h3>
      <p>W sekcji "Zarządzanie Użytkownikami" masz dostęp do pełnej listy zarejestrowanych użytkowników systemu. Dla każdego użytkownika możesz sprawdzić:</p>
      <div class="feature-box">
        <ul>
          <li>Imię i nazwisko</li>
          <li>Adres e-mail</li>
          <li>Rolę w systemie (użytkownik / administrator)</li>
          <li>Datę rejestracji</li>
        </ul>
      </div>

      <h3>Zmiana ról użytkowników</h3>
      <div class="step-list">
        <ol>
          <li>Otwórz sekcję "Zarządzanie Użytkownikami"</li>
          <li>Znajdź użytkownika na liście</li>
          <li>Kliknij opcję zmiany roli</li>
          <li>Wybierz nową rolę (user/admin)</li>
          <li>Zapisz zmiany</li>
        </ol>
      </div>
    </div>

    <div class="help-section">
      <h2>📋 Przypisywanie Zarządców</h2>
      
      <h3>Jak przypisać zarządcę do nieruchomości?</h3>
      <div class="step-list">
        <ol>
          <li>Przejdź do sekcji "Przypisania Zarządców"</li>
          <li>Wybierz nieruchomość z listy rozwijanej</li>
          <li>Wybierz użytkownika, który będzie zarządcą</li>
          <li>Kliknij "Przypisz Zarządcę"</li>
        </ol>
      </div>
      
      <p><strong>Zarządca otrzyma:</strong></p>
      <ul>
        <li>Dostęp do zarządzania wybraną nieruchomością</li>
        <li>Możliwość edycji szczegółów nieruchomości</li>
        <li>Wgląd w historię wynajmów</li>
        <li>Dostęp do zgłoszeń konserwacyjnych</li>
      </ul>
    </div>

    <div class="help-section">
      <h2>🎫 System Zgłoszeń (Tickety)</h2>
      
      <h3>Obsługa zgłoszeń użytkowników</h3>
      <p>System ticketów pozwala na efektywną komunikację z użytkownikami i rozwiązywanie ich problemów.</p>
      
      <div class="step-list">
        <ol>
          <li>Otwórz sekcję "Zgłoszenia" w panelu administratora</li>
          <li>Przeglądaj listę aktywnych zgłoszeń</li>
          <li>Kliknij na zgłoszenie aby zobaczyć szczegóły</li>
          <li>Odpowiedz na zgłoszenie lub zmień jego status</li>
          <li>Dostępne statusy:
            <ul>
              <li><strong>Otwarte</strong> - Nowe, nierozpatrzone zgłoszenie</li>
              <li><strong>W trakcie</strong> - Zgłoszenie jest rozpatrywane</li>
              <li><strong>Zamknięte</strong> - Problem został rozwiązany</li>
            </ul>
          </li>
        </ol>
      </div>
    </div>

    <div class="help-section">
      <h2>📊 Raporty i Statystyki</h2>
      
      <h3>Dostępne raporty</h3>
      <div class="feature-box">
        <ul>
          <li><strong>Raport wynajmów</strong> - Statystyki rezerwacji i przychodów</li>
          <li><strong>Raport użytkowników</strong> - Aktywność użytkowników platformy</li>
          <li><strong>Raport nieruchomości</strong> - Najpopularniejsze oferty</li>
          <li><strong>Raport konserwacyjny</strong> - Zgłoszenia napraw i usterek</li>
        </ul>
      </div>

      <h3>Generowanie raportów</h3>
      <div class="step-list">
        <ol>
          <li>Przejdź do sekcji "Raporty"</li>
          <li>Wybierz typ raportu</li>
          <li>Ustaw zakres dat (jeśli dostępne)</li>
          <li>Kliknij "Generuj Raport"</li>
          <li>Raport można wyeksportować do PDF lub Excel</li>
        </ol>
      </div>
    </div>

    <div class="help-section">
      <h2>📝 Logi Systemowe</h2>
      
      <h3>Przeglądanie logów</h3>
      <p>Logi systemowe zawierają informacje o wszystkich ważnych wydarzeniach w systemie:</p>
      <ul>
        <li>Logowania użytkowników</li>
        <li>Zmiany w nieruchomościach</li>
        <li>Dokonane rezerwacje</li>
        <li>Błędy systemowe</li>
        <li>Działania administracyjne</li>
      </ul>

      <div class="step-list">
        <ol>
          <li>Otwórz sekcję "Logi Systemowe"</li>
          <li>Użyj filtrów aby zawęzić wyniki:
            <ul>
              <li>Filtruj po dacie</li>
              <li>Filtruj po typie zdarzenia</li>
              <li>Filtruj po użytkowniku</li>
            </ul>
          </li>
          <li>Przeglądaj szczegółowe informacje o każdym zdarzeniu</li>
        </ol>
      </div>
    </div>

    <div class="help-section">
      <h2>⚙️ Ustawienia Systemowe</h2>
      
      <h3>Konfiguracja platformy</h3>
      <p>W sekcji Ustawień możesz dostosować działanie systemu:</p>
      <div class="feature-box">
        <ul>
          <li><strong>Ustawienia ogólne</strong> - Nazwa platformy, logo, opis</li>
          <li><strong>Ustawienia email</strong> - Konfiguracja powiadomień</li>
          <li><strong>Ustawienia płatności</strong> - Metody płatności, prowizje</li>
          <li><strong>Ustawienia bezpieczeństwa</strong> - Wymagania dla haseł, sesje</li>
        </ul>
      </div>
    </div>

    <div class="help-section">
      <h2>💬 System Wiadomości</h2>
      
      <h3>Komunikacja z użytkownikami</h3>
      <p>Administrator może komunikować się z użytkownikami przez wbudowany system wiadomości:</p>
      <div class="step-list">
        <ol>
          <li>Przejdź do sekcji "Wiadomości"</li>
          <li>Kliknij "Nowa Wiadomość"</li>
          <li>Wybierz odbiorcę z listy użytkowników</li>
          <li>Wpisz tytuł i treść wiadomości</li>
          <li>Kliknij "Wyślij"</li>
        </ol>
      </div>
      
      <p><strong>Wskazówka:</strong> Możesz również odpowiadać na wiadomości od użytkowników bezpośrednio z zakładki "Odebrane".</p>
    </div>

    <div class="help-section">
      <h2>🔧 Rozwiązywanie Problemów</h2>
      
      <h3>Często spotykane problemy</h3>
      
      <div class="feature-box">
        <h4>❓ Użytkownik nie może się zalogować</h4>
        <p><strong>Rozwiązanie:</strong></p>
        <ol>
          <li>Sprawdź czy konto użytkownika jest aktywne</li>
          <li>Zresetuj hasło użytkownika</li>
          <li>Sprawdź logi systemowe pod kątem błędów logowania</li>
        </ol>
      </div>

      <div class="feature-box">
        <h4>❓ Problem z wysyłaniem zdjęć nieruchomości</h4>
        <p><strong>Rozwiązanie:</strong></p>
        <ol>
          <li>Sprawdź czy folder "uploads" ma odpowiednie uprawnienia (chmod 755)</li>
          <li>Sprawdź rozmiar pliku (max 5MB)</li>
          <li>Sprawdź format pliku (PNG, JPG, JPEG)</li>
        </ol>
      </div>

      <div class="feature-box">
        <h4>❓ Błąd połączenia z bazą danych</h4>
        <p><strong>Rozwiązanie:</strong></p>
        <ol>
          <li>Sprawdź plik config.php - czy dane dostępowe są poprawne</li>
          <li>Sprawdź czy serwer MySQL jest uruchomiony</li>
          <li>Sprawdź logi błędów serwera</li>
        </ol>
      </div>
    </div>

    <div class="help-section">
      <h2>🛡️ Bezpieczeństwo</h2>
      
      <h3>Najlepsze praktyki</h3>
      <ul>
        <li>✅ Regularnie zmieniaj hasło administratora</li>
        <li>✅ Używaj silnych haseł (min. 12 znaków, cyfry, znaki specjalne)</li>
        <li>✅ Regularnie twórz kopie zapasowe bazy danych</li>
        <li>✅ Monitoruj logi systemowe pod kątem podejrzanych działań</li>
        <li>✅ Aktualizuj system i wszystkie komponenty</li>
        <li>✅ Ogranicz liczbę administratorów do minimum</li>
        <li>✅ Wyloguj się po zakończeniu pracy</li>
      </ul>
    </div>

    <div class="help-section">
      <h2>📞 Pomoc Techniczna</h2>
      <p>Jeśli potrzebujesz dodatkowej pomocy lub napotkasz problem, który nie został opisany w tej dokumentacji:</p>
      <ul>
        <li>📧 Email: support@smartrent.pl</li>
        <li>📱 Telefon: +48 123 456 789</li>
        <li>💬 Chat na żywo: dostępny w godzinach 9:00-17:00</li>
      </ul>
      <p style="margin-top: 20px; color: var(--muted); font-size: 0.9rem;">
        Dokumentacja aktualizowana: Listopad 2025 | Wersja 1.0
      </p>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>

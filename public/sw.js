// DriveJob Service Worker
//
// ΓΙΑΤΙ ΞΑΝΑΓΡΑΦΤΗΚΕ: η προηγούμενη έκδοση ήταν cache-first για ΤΑ ΠΑΝΤΑ —
// αν κάτι υπήρχε στην τοπική μνήμη του browser, το σέρβιρε χωρίς καν να
// ρωτήσει τον server. Όταν το site μπήκε σε Maintenance Mode, ο browser
// αποθήκευσε τις αποκρίσεις εκείνης της περιόδου· μετά την επαναφορά
// εξακολουθούσε να τις σερβίρει, και οι νέες εκδόσεις σελίδων δεν έφταναν
// ποτέ στον χρήστη. Το CACHE_NAME ήταν σταθερό, οπότε τίποτα δεν καθαριζόταν.
//
// Η νέα λογική:
//   • Σελίδες (navigation): ΠΑΝΤΑ δίκτυο πρώτα. Cache μόνο ως εφεδρεία όταν
//     ο χρήστης είναι εκτός σύνδεσης.
//   • Στατικά (css/js/img/fonts): stale-while-revalidate — γρήγορη απόκριση
//     από cache, αλλά ΠΑΝΤΑ ανανέωση στο παρασκήνιο.
//   • Τίποτα δυναμικό ή ευαίσθητο δεν μπαίνει σε cache.
//
// ΟΤΑΝ ΑΛΛΑΖΕΙ ΚΑΤΙ ΕΔΩ, ΑΝΕΒΑΣΕ ΤΟ CACHE_VERSION — έτσι καθαρίζονται τα
// παλιά cache σε κάθε browser που έχει επισκεφθεί το site.

// ΟΤΑΝ ΤΟ EDGE CACHE ΤΟΥ ΠΑΡΟΧΟΥ ΚΟΛΛΗΣΕΙ ΣΕ ΑΥΤΟ ΤΟ ΑΡΧΕΙΟ:
// Το StackCP του netmind ΔΕΝ έχει κουμπί Purge. Ο μόνος τρόπος να
// πεταχτεί μια αποθηκευμένη απόκριση είναι να ΑΛΛΑΞΕΙ το περιεχόμενο
// του αρχείου — τότε αλλάζει το ETag, το cache επικυρώνει, και παίρνει
// τη νέα απόκριση μαζί με τις σωστές κεφαλίδες.
//
// Ανέβασε το CACHE_VERSION παρακάτω. Δεν είναι χάκ: ούτως ή άλλως
// θέλουμε νέα έκδοση κάθε φορά που αλλάζει η λογική εδώ, ώστε να
// καθαρίζουν και τα cache στους browsers των χρηστών.
const CACHE_VERSION = 'v4';
const STATIC_CACHE = `drivejob-static-${CACHE_VERSION}`;
const PAGES_CACHE = `drivejob-pages-${CACHE_VERSION}`;

/**
 * Ελάχιστα αρχεία που θέλουμε διαθέσιμα εκτός σύνδεσης.
 *
 * Η παλιά λίστα περιείχε '/css/style.css' — αρχείο που δεν υπήρξε ποτέ (το
 * σωστό είναι styles.css). Επειδή το cache.addAll() αποτυγχάνει ΟΛΟΚΛΗΡΟ αν
 * έστω ένα URL αποτύχει, το static cache δεν γέμιζε ποτέ.
 */
const STATIC_RESOURCES = [
  '/css/styles.css',
  '/img/logo.png',
  '/manifest.json',
];

/**
 * Διαδρομές που ΔΕΝ μπαίνουν ποτέ σε cache.
 *
 * Οτιδήποτε αφορά ταυτότητα, διαχείριση, προγραμματισμένες εργασίες ή
 * κατάσταση συστήματος πρέπει να φτάνει πάντα φρέσκο από τον server.
 */
const NEVER_CACHE = [
  '/auth/', '/login', '/logout', '/register',
  '/admin', '/cron/', '/health', '/gdpr/',
  '/job-applications/', '/messages', '/conversation',
];

/**
 * ΤΟ ΣΦΑΛΜΑ ΠΟΥ ΕΙΧΕ ΑΥΤΗ Η ΣΥΝΑΡΤΗΣΗ:
 *
 * Ο έλεγχος ήταν `pathname.startsWith(prefix)`. Η λίστα περιέχει
 * '/register' — αλλά οι πραγματικές μας διαδρομές είναι
 * '/drivers/register' και '/companies/register'. Δεν ΞΕΚΙΝΟΥΝ με
 * '/register', οπότε δεν έπιαναν, και οι φόρμες εγγραφής αποθηκεύονταν
 * κανονικά στο cache του browser.
 *
 * Μια σελίδα με φόρμα σε cache σημαίνει ΠΑΛΙΟ CSRF token: ο χρήστης
 * βλέπει τη χθεσινή σελίδα, υποβάλλει, και το αίτημα απορρίπτεται.
 *
 * Τώρα ελέγχουμε αν το τμήμα εμφανίζεται ΟΠΟΥΔΗΠΟΤΕ στη διαδρομή.
 */
function isNeverCached(pathname) {
  return NEVER_CACHE.some((fragment) => pathname.includes(fragment));
}

/**
 * Κάθε σελίδα που περιέχει φόρμα φέρει CSRF token, και κάθε token σε
 * cache είναι μελλοντική απόρριψη. Ο κανόνας είναι απλός και σκόπιμα
 * γενναιόδωρος: αν η διαδρομή μυρίζει φόρμα, δεν αποθηκεύεται.
 */
function looksLikeForm(pathname) {
  return /(register|create|edit|apply|new|profile|settings|password|upload)/i.test(pathname);
}

function isStaticAsset(pathname) {
  return /\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf)$/i.test(pathname);
}

// ─────────────────────────────────────────────────────── Εγκατάσταση

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      // addAll ματαιώνεται ολόκληρο σε μία αποτυχία — προσθέτουμε ένα προς ένα
      // ώστε ένα αρχείο που λείπει να μη ρίχνει όλη την εγκατάσταση.
      .then((cache) => Promise.all(
        STATIC_RESOURCES.map((url) =>
          cache.add(url).catch(() => {
            console.warn('[SW] δεν μπήκε σε cache:', url);
          })
        )
      ))
      .then(() => self.skipWaiting())
  );
});

// ─────────────────────────────────────────────────────── Ενεργοποίηση

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((names) => Promise.all(
        names
          .filter((name) => name !== STATIC_CACHE && name !== PAGES_CACHE)
          .map((name) => {
            console.log('[SW] διαγραφή παλιού cache:', name);
            return caches.delete(name);
          })
      ))
      .then(() => self.clients.claim())
  );
});

// ─────────────────────────────────────────────────────── Αιτήματα

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  if (url.origin !== location.origin) return;
  if (isNeverCached(url.pathname)) return;      // αφήνουμε τον browser να το κάνει κανονικά

  // Σελίδες με φόρμα δεν μπαίνουν ποτέ σε cache: το CSRF token τους παλιώνει
  // και η υποβολή απορρίπτεται. Τα στατικά (.css/.js) εξαιρούνται από τον
  // έλεγχο — ένα «profile.css» δεν είναι φόρμα.
  if (!isStaticAsset(url.pathname) && looksLikeForm(url.pathname)) return;

  if (url.search && !isStaticAsset(url.pathname)) return;  // δυναμικά με παραμέτρους: πάντα φρέσκα

  if (isStaticAsset(url.pathname)) {
    event.respondWith(staleWhileRevalidate(request));
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(networkFirst(request));
  }
});

/**
 * Σελίδες: δίκτυο πρώτα, cache μόνο ως εφεδρεία εκτός σύνδεσης.
 *
 * Αυτό είναι το σημείο που έσπασε παλιότερα. Μια σελίδα από cache μπορεί να
 * είναι σελίδα συντήρησης, παλιά έκδοση, ή απόκριση από περίοδο που κάτι δεν
 * δούλευε. Δεν την εμπιστευόμαστε όσο υπάρχει δίκτυο.
 */
async function networkFirst(request) {
  try {
    const response = await fetch(request);

    // Μόνο κανονικές, επιτυχείς αποκρίσεις αξίζουν αποθήκευση.
    if (response.ok && response.type === 'basic') {
      const copy = response.clone();
      caches.open(PAGES_CACHE).then((cache) => cache.put(request, copy));
    }

    return response;
  } catch (error) {
    const cached = await caches.match(request);
    if (cached) return cached;

    return new Response(
      '<!DOCTYPE html><html lang="el"><head><meta charset="utf-8">'
      + '<title>Χωρίς σύνδεση</title></head><body style="font:16px/1.6 system-ui;padding:2rem;text-align:center">'
      + '<h1>Δεν υπάρχει σύνδεση</h1>'
      + '<p>Έλεγξε τη σύνδεσή σου και δοκίμασε ξανά.</p></body></html>',
      { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
  }
}

/**
 * Στατικά: γρήγορη απόκριση από cache, αλλά πάντα ανανέωση στο παρασκήνιο.
 * Έτσι ένα νέο CSS φτάνει στον χρήστη το αργότερο στην επόμενη φόρτωση.
 */
async function staleWhileRevalidate(request) {
  const cache = await caches.open(STATIC_CACHE);
  const cached = await cache.match(request);

  const network = fetch(request)
    .then((response) => {
      if (response.ok && response.type === 'basic') {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(() => cached);

  return cached || network;
}

// ─────────────────────────────────────────────── Ειδοποιήσεις push

self.addEventListener('push', (event) => {
  if (!event.data) return;

  let data;
  try {
    data = event.data.json();
  } catch (error) {
    return;
  }

  event.waitUntil(
    self.registration.showNotification(data.title || 'DriveJob', {
      body: data.body || '',
      icon: '/img/icons/icon-192x192.png',
      badge: '/img/icons/icon-72x72.png',
      data: { url: data.url || '/' },
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(clients.openWindow(event.notification.data?.url || '/'));
});

/**
 * Μήνυμα από τη σελίδα: καθαρισμός όλων των cache.
 *
 * Δίνει τρόπο να ξεκολλήσει ένας browser χωρίς να χρειάζεται ο χρήστης να
 * μπει στα DevTools.
 */
self.addEventListener('message', (event) => {
  if (event.data === 'dj-clear-caches') {
    event.waitUntil(
      caches.keys()
        .then((names) => Promise.all(names.map((name) => caches.delete(name))))
        .then(() => event.source && event.source.postMessage('dj-caches-cleared'))
    );
  }
});

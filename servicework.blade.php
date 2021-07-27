var cacheName = "{{ $cacheName }}";
<?php
$filesToCache = json_encode($filesToCache);
echo "var filesToCache = ". $filesToCache . ";\n";
?>

// When the service worker is installing, open the cache and add the precache resources to it
self.addEventListener('install', (event) => {
  console.log('Service worker install event!');
  if(cacheName != "PWA") {
    event.waitUntil(caches.open(cacheName).then((cache) => cache.addAll(filesToCache)));
  }
});

self.addEventListener('activate', (event) => {
  var cacheAllowlist = [cacheName];
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          if (cacheAllowlist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );

  console.log('Service worker activate event!');
});

// When there's an incoming fetch request, try and respond with a precached resource, otherwise fall back to the network
// self.addEventListener('fetch', function(event) {
//   event.respondWith(
//     caches.match(event.request)
//       .then(function(response) {
//         // Cache hit - return response
//         if (response) {
//           return response;
//         }
//         return fetch(event.request);
//       }
//     )
//   );
// });
// 
self.addEventListener('fetch', (e) => {
  e.respondWith((async () => {
    const r = await caches.match(e.request);
    console.log(`[Service Worker] Fetching resource: ${e.request.url}`);
    if (r) { return r; }
    const response = await fetch(e.request);
    const cache = await caches.open(cacheName);
    console.log(`[Service Worker] Caching new resource: ${e.request.url}`);

    if(!e.request.url.includes("webctrl") && e.request.method == "GET" && response.status != "206") {
      cache.put(e.request, response.clone());
    }
    return response;
  })());
});

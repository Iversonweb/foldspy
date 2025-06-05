window.addEventListener('load', () => {
    // Array to store the URLs of visible links
    const visibleLinks = [];
    
    // Flag to track if logging has occurred
    let hasLogged = false;
  
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                visibleLinks.push(entry.target.href);
            }
        });
    
        // Debounce trigger to avoid duplicates
        setTimeout(() => {
            if (visibleLinks.length > 0) {
                sendVisibleLinks([...new Set(visibleLinks)]);
            }
        }, 500);
    }, {
        root: null,
        rootMargin: '0px',
        threshold: 1.0,
    });
  
    document.querySelectorAll('a[href]').forEach((link) => {
        observer.observe(link);
    });
  
    function sendVisibleLinks(hrefs) {
        // Check if logging has already occurred and set the flag to true if not
        if (hasLogged) return;
        hasLogged = true;

        
        // Send visible links to the server for logging
        fetch(FoldSpyData.endpoint, {
            method: 'POST',
            headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': FoldSpyData.nonce,
            },
            body: JSON.stringify({
                screenWidth: window.innerWidth,
                screenHeight: window.innerHeight,
                hrefs,
            }),
        }).then(res => {
            if (res.ok) {
                console.log('FoldSpy logged successfully!');
            } else {
                console.warn('FoldSpy logging failed!');
            }
        });
    }
  });
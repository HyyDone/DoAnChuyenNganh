function postNew(content) {
    fetch('/api/posts.php', { method: 'POST', body: new URLSearchParams({ content }) })
        .then(r => r.json()).then(console.log);
}
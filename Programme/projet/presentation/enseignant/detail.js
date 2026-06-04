function toggleLike(memoireId) {
    fetch('like_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'memoire_id=' + memoireId
    })
    .then(response => response.json())
    .then(data => {
        if (data.erreur) return;

        const btn   = document.getElementById('btn-like');
        const icon  = document.getElementById('like-icon');
        const count = document.getElementById('like-count');

        count.textContent = data.nb_likes;

        if (data.action === 'like') {
            icon.textContent = '❤️';
            btn.classList.remove('btn-outline');
            btn.classList.add('btn-danger');
        } else {
            icon.textContent = '🤍';
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-outline');
        }
    });
}

<script>
setInterval(() => {
    fetch("simulateur.php")
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(err => console.error(err));
}, 5000);
</script>

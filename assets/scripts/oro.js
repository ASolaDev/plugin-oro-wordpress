document.addEventListener("DOMContentLoaded", function () {

    function refrescarPrecioOro() {
        fetch(gpl_ajax.ajax_url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "gpl_refresh"
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const contenedor = document.getElementById("gpl-gold-price");
                    if (contenedor) contenedor.innerHTML = data.data.html;
                }
            })
            .catch(err => console.error("Error actualizando el precio del oro:", err));
    }

    // Refrescar cada 5 minutos (300000 ms)
    setInterval(refrescarPrecioOro, 300000);
});


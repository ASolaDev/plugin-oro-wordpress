document.addEventListener("DOMContentLoaded", function () {

    async function refrescarPrecioOro() {
        try {
            const response = await fetch(gpl_ajax.ajax_url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({ action: "gpl_refresh" })
            });

            if (!response.ok) {
                console.error('gpl: respuesta no OK', response.status);
                mostrarError('No se pudo actualizar el precio del oro.');
                return;
            }

            const data = await response.json();
            if (!data || !data.success) {
                console.error('gpl: error en la respuesta AJAX', data);
                mostrarError('Error al obtener datos del servidor.');
                return;
            }

            const contenedor = document.getElementById("gpl-gold-price");
            if (contenedor) contenedor.innerHTML = data.data.html;

        } catch (err) {
            console.error("Error actualizando el precio del oro:", err);
            mostrarError('Error de red al actualizar el precio.');
        }
    }

    function mostrarError(mensaje) {
        const contenedor = document.getElementById("gpl-gold-price");
        if (contenedor) {
            contenedor.innerHTML = "<p class='gpl-error'>" + mensaje + "</p>";
        }
    }

    // Cargar inicialmente y luego cada 5 minutos (300000 ms)
    refrescarPrecioOro();
    setInterval(refrescarPrecioOro, 300000);
});


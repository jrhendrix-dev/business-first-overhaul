<?php
session_start();
$pageTitle = "Gracias!";
include_once __DIR__ . '/../views/header.php';

?>

<div class="container text-center mt-5">
    <h1 class="mb-4">¡Gracias por contactarnos!</h1>
    <p class="lead">Tu mensaje ha sido recibido correctamente. Nos pondremos en contacto contigo lo antes posible.</p>
    <div class="text-center mt-4 mb-5">
        <a href="/home">
            <button class="return-button">Volver a la página principal</button>
        </a>
    </div>
</div>

<?php include_once __DIR__ . '/../views/footer.php'; ?>

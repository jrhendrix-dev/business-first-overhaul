<?php

/*
 These ini_set calls configure how PHP will create the session cookie.
They must be set before the session is started, so that the cookie is created with the correct flags.
If you set them after session_start(), the session cookie may already have been sent to the browser without those flags.
 */
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Only if using HTTPS

session_start();
?>

<!-- Header -->
<?php
$pageTitle = "Business First Language Center";
include_once __DIR__ . '/../views/header.php';
?>
<div id="banner-container">
    <img src="assets/pics/banner.jpg" alt="vista oficina corporativa" id="Title-Image">
</div>

<div class="cuerpo">


</div>

<div class="Row-Header">
    <h2>Servicios que ofrecemos</h2>
</div>

<div class="rows">

    <div class="Row-Even">
        <a id="Sección-Inglés-Corporativo" class="anchor"></a>
        <h2>Inglés corporativo para empresas <i class="fas fa-arrow-right link-icon icon-button"></i></h2>
        <img src="assets/pics/presentation.png" alt="office presentation" class="row-pic" />
        <p class="row-text" id="row-text">
            Mejora el nivel de inglés de tu equipo con nuestros cursos adaptados a empresas.<br><br>
            🔹 Evita malentendidos en reuniones, presentaciones y negociaciones internacionales.<br>
            🔹 Formación práctica para comunicarse con clientes y socios globales.<br>
            🔹 Tu inversión se traduce en mayor confianza, productividad y oportunidades.<br>
            🔹 Metodología cómoda, flexible y adaptada a tu sector.<br><br>
            💼 La mejor solución para equipos que quieren crecer sin fronteras.
        </p>
        <a class="panel-link" href="javascript:void(0)" onclick="window.location = 'ingles-corporativo'"></a>
    </div>
    <div class="Row-Uneven">
        <a  id="Sección-Exámenes-Oficiales" class="anchor"></a>
        <h2>Preparación de exámenes oficiales<i class="fas fa-arrow-right link-icon icon-button"></i></h2>
        <img src="assets/pics/estudiantes1.png" alt="estudiantes universitarios" class="row-pic" />
        <p class="row-text">
            Prepárate con nosotros para alcanzar tus metas académicas y profesionales.<br><br>
            🎯 Aprende inglés para:<br>
            ▪ Estudiar en una universidad extranjera<br>
            ▪ Participar en el programa Erasmus<br>
            ▪ Obtener certificados oficiales de inglés<br><br>
            💡 Clases enfocadas en TOEFL, TOEIC y Cambridge.<br>
            📚 Método práctico, personalizado y eficaz.<br><br>
            <strong>¡Matricúlate hoy y da el siguiente paso en tu futuro!</strong>
        </p>
        <a class="panel-link" href="javascript:void(0)" onclick="window.location = 'examenes'"></a>
    </div>
    <div class="Row-Even">
        <a id="Sección-Español-Extranjeros" class="anchor"></a>
        <h2>Español para extranjeros <i class="fas fa-arrow-right link-icon icon-button"></i></h2>
        <img src="assets/pics/espanol.png" alt="aprende español" class="row-pic" />
        <p class="row-text">
            🌍 El español es uno de los idiomas más hablados del mundo.<br><br>
            ¿Vives, trabajas o estudias en España? ¿Quieres comunicarte con fluidez?<br><br>
            📖 Nuestro curso te enseña:<br>
            ▪ Estructura gramatical y vocabulario útil<br>
            ▪ Expresiones reales para la vida diaria y profesional<br>
            ▪ A desenvolverte en viajes, reuniones y entornos laborales<br><br>
            ✍️ Aprende con nosotros de forma cómoda y práctica.<br>
            ¡Inscríbete hoy y empieza a hablar español con confianza!
        </p>
        <a class="panel-link" href="javascript:void(0)" onclick="window.location = 'clases-espanol'"></a>

    </div>
</div>

<!-- Footer -->
<?php include_once __DIR__ . '/../views/footer.php'; ?>


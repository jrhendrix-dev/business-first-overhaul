
<?php
session_start();
$pageTitle = "Clases Español";
include_once __DIR__ . '/../views/header.php';
?>

  
  
<h1 class="TítuloPágina">CLASES DE ESPAÑOL PARA EXTRANJEROS</h1>
<div class="wrapper">
        <div class="pageContent flex-column info-column">    
                
                <br>

                <h2 class="TítuloMenor">Nuestros cursos de español están dirigidos a:</h2>

                <ul>
                    <li>Estudiantes de Erasmus o intercambio extranjeros.</li>
                    <li>Trabajadores extranjeros que necesitan mejorar su español.</li>
                    <li>Extranjeros que quieran comunicarse en español para viajar.</li>
                </ul>
<br>                
                <h2 class="TítuloMenor">Modalidades de clases:</h2>
                <ul>
                    <li>Aprendizaje estructurado que se desarrolla en contextos reales.</li>
                    <li>Grupos reducidos y agrupados según el nivel.</li>
                    <li>Aprendizaje de la cultura y costumbres españolas.</li>
                    <li>Prácticas de vocabulario cotidiano para trabajar o socializar.</li>
                </ul>
<br>

                <h2 class="TítuloMenor">La academia proporciona</h2>
                <ul>
                    <li>Profesores nativos cualificados</li>
                    <li>Tecnologías punta como: pizarras electrónicas, portátiles, wifi, etc.</li>
                    <li>Instalaciones amplias, cómodas y con un ambiente agradable</li>
                    <li>Métodos modernos, dinámicos y con rápidos resultados</li>
                </ul>
                
<br>
                <h2 class="TítuloMenor">Niveles de Español que ofrecemos:</h2>
                <ul>
                    <li>Nivel A2 (Básico)</li>
                    <li>Nivel B2 (Avanzado)</li>
                    <li>Nivel B1 (intermedio)</li>    
                </ul>
                <br>
                <ul>
                    <li id="Bulletchange">Solicita información rellenando el formulario con tus datos, nos pondremos en contacto contigo
                        para informarte<br> sobre nuesros precios, niveles, horarios, modalidades, etc.
                    Responderemos a todas tus preguntas y podrás visitar<br> nuestras instalaciones. </li>
                </ul>


            <form id="entryform" method="POST" action="/create">
                <table class="table table-bordered infotable">
                    <thead>
                    <tr>
                        <th colspan="2"><h2>Formulario</h2></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th>Nombre:</th>
                        <td>
                            <input type="text" name="nombre" maxlength="20" class="entry-form-input" required>
                            <span id="nombremsg" class="text-danger"></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Apellidos:</th>
                        <td>
                            <input type="text" name="apellidos" maxlength="20" class="entry-form-input" required>
                            <span id="apellidosmsg" class="text-danger"></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Teléfono:</th>
                        <td>
                            <input type="tel" name="teléfono" maxlength="9" pattern="[0-9]{9}" class="entry-form-input" required>
                            <span id="telmsg" class="text-danger"></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>
                            <input type="email" name="email" class="entry-form-input" required>
                            <span id="emailmsg" class="text-danger"></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Mensaje:</th>
                        <td><input type="text" name="mensaje" maxlength="50" class="entry-form-input"></td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="2">
                            <div class="container">
                                <button type="submit" class="btn btn-success">Enviar</button>
                                <input type="reset" class="btn btn-danger"/><br>
                                <span id="form_log" class="text-danger"></span>
                            </div>
                        </th>
                    </tr>
                    </tfoot>
                </table>
            </form>
        </div> <!--Div pagecontent End-->   


        <div class="pageSide">
                <img src="assets/pics/aprenderespanol.png" alt="pizarra de aprende español"/>
                <p>Welcome.<br>
                Aprenderás español "Sí o sí"</p>
                <br>
                <img src="assets/pics/español1.png" alt="estudiantes de español"/>
                <p>Si quieres hablar español.<br>
                   Tenemos un curso diseñado para ti.</p>
        </div><!-- DIV pageside end-->

</div> <!-- Div Wrapper End-->
<!-- Footer -->
<?php include_once __DIR__ . '/../views/footer.php'; ?>

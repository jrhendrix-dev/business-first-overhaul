<?php
session_start();
$pageTitle = "Exámenes";
include_once __DIR__ . '/../views/header.php';
?>

        <h1 class="TítuloPágina">PREPARACIÓN PARA EXÁMENES OFICIALES</h1>
<div class="wrapper">
        <div class="pageContent flex-column info-column">    
                
                <br>

                <h2 class="TítuloMenor">Cursos de preparación a EO dirigidos a:</h2>

                <ul>
                    <li>Estudiantes que quieren acceder a Universidades extranjeras.</li>
                    <li>Estudiantes que quieren participar en el programa Erasmus.</li>
                    <li>Trabajadores que necesitan el certificado oficial de idiomas.</li>
                </ul>
<br>                
                <h2 class="TítuloMenor">Modalidades de clases:</h2>
                <ul>
                    <li>Preparación para exámenes oficiales de: TOELF, TOEIC, SAT y Cambridge.</li>
                    <li>Grupos reducidos y agrupados según el nivel.</li>
                    <li>Cursos anuales o intensivos en verano.</li>
                    <li>Simulacros de exámenes para analizar tu progreso.</li>
                </ul>
<br>
                <h2 class="TítuloMenor">La academia proporciona:</h2>
                <ul>
                    <li>Profesores nativos cualificados</li>
                    <li>Tecnologías punta como: pizarras electrónicas, portátiles, wifi, etc.</li>
                    <li>Instalaciones amplias, cómodas y con un ambiente agradable</li>
                    <li>Métodos modernos, dinámicos y con resultados rápidos</li>
                </ul>
                
                <h2 class="TítuloMenor">Niveles de preparación de inglés que ofrecemos:</h2>
                <ul>
                    <li>A2 Level (Basic)</li>
                    <li>B2 Level (Intermediate)</li>
                    <li>B1 Level (Medium)</li>
                    <li>C1 Level (Advanced)</li>
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
                <img src="assets/pics/estudiantes.png" alt="equipo de estudiantes"/>
                <p>Learn fast, expect the best.</p>
                <br>
                <img src="assets/pics/Estudiantes de inglés.png" alt="estudiantes de inglés"/>
                <p>Estudia con nosotros.<br>
                   Invierte en tu futuro, no te arrepentirás.</p>
        </div><!-- DIV pageside end-->

</div> <!-- Div Wrapper End-->
<!-- Footer -->
<?php include_once __DIR__ . '/../views/footer.php'; ?>

-- ============================================
-- Base de datos: test_vocacional - COMPLETA
-- ============================================

-- Crear base de datos
DROP DATABASE IF EXISTS test_vocacional;

-- ============================================
-- Tabla: preguntas
-- ============================================
CREATE TABLE preguntas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria ENUM('ciencias', 'tecnologia', 'humanidades', 'artes', 'salud', 'negocios') NOT NULL,
    tipo ENUM('intereses', 'habilidades', 'valores') NOT NULL,
    pregunta TEXT NOT NULL,
    peso INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria_tipo (categoria, tipo)
);

-- ============================================
-- Tabla: resultados_test
-- ============================================
CREATE TABLE resultados_test (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    fecha_test TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    puntajes_json TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_fecha (usuario_id, fecha_test)
);

-- ============================================
-- Tabla: respuestas_detalle
-- IMPORTANTE: respuesta DEBE SER 0 O 1 ÚNICAMENTE
-- ============================================
CREATE TABLE respuestas_detalle (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    respuesta TINYINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES resultados_test(id) ON DELETE CASCADE,
    FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE,
    INDEX idx_test_pregunta (test_id, pregunta_id),
    CONSTRAINT chk_respuesta CHECK (respuesta IN (0, 1))
);
);

-- Agregar FK a usuarios para instituciones
ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_institucion 
FOREIGN KEY (institucion_id) REFERENCES instituciones_educativas(id) ON DELETE SET NULL;

-- ============================================
-- Datos iniciales
-- ============================================

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (username, password, email, nombre, apellido, rol) 
VALUES ('admin', MD5('admin123'), 'admin@colegio.edu', 'Administrador', 'Sistema', 'administrador');

-- Insertar preguntas de ejemplo
INSERT INTO preguntas (categoria, tipo, pregunta, peso) VALUES
-- Ciencias - Intereses
('ciencias', 'intereses', 'Me gusta realizar experimentos en el laboratorio', 2),
('ciencias', 'intereses', 'Disfruto aprender sobre el funcionamiento del cuerpo humano', 2),
('ciencias', 'intereses', 'Me interesa investigar sobre nuevos descubrimientos científicos', 3),

-- Ciencias - Habilidades
('ciencias', 'habilidades', 'Sé aplicar el método científico para resolver problemas', 2),
('ciencias', 'habilidades', 'Tengo facilidad para entender conceptos de biología y química', 2),
('ciencias', 'habilidades', 'Puedo analizar datos y sacar conclusiones lógicas', 3),

-- Ciencias - Valores
('ciencias', 'valores', 'Considero importante contribuir al avance de la ciencia', 3),
('ciencias', 'valores', 'Valoro la precisión y exactitud en el trabajo científico', 2),

-- Tecnología - Intereses
('tecnologia', 'intereses', 'Me apasiona aprender sobre nuevas tecnologías', 3),
('tecnologia', 'intereses', 'Disfruto programar y crear aplicaciones', 3),
('tecnologia', 'intereses', 'Me interesa saber cómo funcionan los dispositivos electrónicos', 2),

-- Tecnología - Habilidades
('tecnologia', 'habilidades', 'Sé programar en al menos un lenguaje de programación', 3),
('tecnologia', 'habilidades', 'Puedo resolver problemas técnicos de forma lógica', 2),
('tecnologia', 'habilidades', 'Tengo facilidad para aprender nuevas herramientas digitales', 2),

-- Tecnología - Valores
('tecnologia', 'valores', 'Considero que la tecnología puede mejorar nuestras vidas', 3),
('tecnologia', 'valores', 'Valoro la innovación y el desarrollo tecnológico', 2),

-- Humanidades - Intereses
('humanidades', 'intereses', 'Me gusta leer sobre historia y cultura', 2),
('humanidades', 'intereses', 'Disfruto debatiendo sobre temas sociales y políticos', 2),
('humanidades', 'intereses', 'Me interesa entender el comportamiento humano', 3),

-- Humanidades - Habilidades
('humanidades', 'habilidades', 'Sé expresarme bien por escrito y oralmente', 3),
('humanidades', 'habilidades', 'Puedo analizar textos y extraer ideas principales', 2),
('humanidades', 'habilidades', 'Tengo facilidad para aprender idiomas', 2),

-- Humanidades - Valores
('humanidades', 'valores', 'Considero importante preservar la cultura y tradiciones', 3),
('humanidades', 'valores', 'Valoro la diversidad de opiniones y perspectivas', 2),

-- Artes - Intereses
('artes', 'intereses', 'Me gusta dibujar, pintar o crear arte visual', 3),
('artes', 'intereses', 'Disfruto tocando un instrumento musical', 3),
('artes', 'intereses', 'Me interesa el diseño gráfico y visual', 2),

-- Artes - Habilidades
('artes', 'habilidades', 'Tengo habilidades para el dibujo o la pintura', 2),
('artes', 'habilidades', 'Sé tocar algún instrumento musical', 2),
('artes', 'habilidades', 'Puedo crear composiciones artísticas originales', 3),

-- Artes - Valores
('artes', 'valores', 'Considero que el arte es fundamental para la sociedad', 3),
('artes', 'valores', 'Valoro la expresión artística como forma de comunicación', 2),

-- Salud - Intereses
('salud', 'intereses', 'Me interesa ayudar a las personas a mejorar su salud', 3),
('salud', 'intereses', 'Disfruto aprender sobre el cuerpo humano y su funcionamiento', 2),
('salud', 'intereses', 'Me gusta participar en actividades de bienestar', 2),

-- Salud - Habilidades
('salud', 'habilidades', 'Sé brindar primeros auxilios básicos', 2),
('salud', 'habilidades', 'Puedo transmitir información sobre salud de forma clara', 2),
('salud', 'habilidades', 'Tengo paciencia y empatía para cuidar a otros', 3),

-- Salud - Valores
('salud', 'valores', 'Considero que la salud es lo más importante en la vida', 3),
('salud', 'valores', 'Valoro el trabajo de los profesionales de la salud', 2),

-- Negocios - Intereses
('negocios', 'intereses', 'Me interesa aprender sobre economía y finanzas', 2),
('negocios', 'intereses', 'Disfruto participando en proyectos de emprendimiento', 3),
('negocios', 'intereses', 'Me gusta negociar y cerrar tratos', 2),

-- Negocios - Habilidades
('negocios', 'habilidades', 'Sé organizar y planificar proyectos', 2),
('negocios', 'habilidades', 'Puedo trabajar bien en equipo y liderar cuando es necesario', 3),
('negocios', 'habilidades', 'Tengo facilidad para los números y cálculos', 2),

-- Negocios - Valores
('negocios', 'valores', 'Considero que el emprendimiento impulsa la economía', 3),
('negocios', 'valores', 'Valoro la ética y la responsabilidad en los negocios', 2);

-- ============================================
-- FIN DEL SCRIPT
-- ============================================



--- 
INSERT INTO instituciones_educativas (nombre, codigo, tipo) VALUES
('UNIDAD EDUCATIVA FISCAL “AMABLE ARAUZ”', '17H01705', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "ALANGASÍ"', '17H01448', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "DR. RICARDO CORNEJO ROSALES', '17H01041', 'Fiscal'),
('UNIDAD EDUCATIVA "GONZALO ESCUDERO"', '17H01219', 'Fiscal'),
('UNIDAD EDUCATIVA “ÁNGEL POLIBIO CÓRDOVA SANTANDER”', '17H01177', 'Fiscal'),
('UNIDAD EDUCATIVA PICHINCHA', '17H01836', 'Fiscal'),
('UNIDAD EDUCATIVA "CARLOS AGUILAR"', '17H01744', 'Fiscal'),
('UNIDAD EDUCATIVA LEONIDAS PLAZA GUTIERREZ', '17H00177', 'Fiscal'),
('UNIDAD EDUCATIVA "ESPECIALIZADA DEL NORTE"', '17H00184', 'Fiscal'),
('UNIDAD EDUCATIVA SIXTO DURAN BALLEN', '17H00187', 'Fiscal'),
('UNIDAD EDUCATIVA CARCELÉN', '17H00961', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "LUIS G. TUFIÑO"', '17H00204', 'Fiscal'),
('UNIDAD EDUCATIVA PEDRO JOSE ARTETA', '17H01820', 'Fiscal'),
('UNIDAD EDUCATIVA "PADRE MENTHEN"', '17H01839', 'Fiscal'),
('UNIDAD EDUCATIVA ESPECIALIZADA FISCAL DE AUDICIÓN Y LENGUAJE ENRIQUETA SANTILLÁN', '17H00398', 'Fiscal'),
('UNIDAD EDUCATIVA DE FUERZAS ARMADAS LICEO NAVAL QUITO "COMANDANTE CÉSAR ENDARA PEÑAHERRERA"', '17H01712', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCOMISIONAL “SAN JERÓNIMO”', '17H01947', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCAL ANTISANA', '17H01944', 'Fiscal'),
('UNIDAD EDUCATIVA "BENJAMÍN CARRIÓN"', '17H01485', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL CONOCOTO', '17H01660', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL ABELARDO FLORES', '17H01718', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "J.M. JIJÓN CAAMAÑO Y FLORES"', '17H01476', 'Fiscal'),
('UNIDAD EDUCATIVA "ANTONIO NARIÑO"', '17H00444', 'Fiscal'),
('UNIDAD EDUCATIVA "BUENA VENTURA"', '17H00362', 'Fiscal'),
('UNIDAD EDUCATIVA "CELIANO MONGE"', '17H01053', 'Fiscal'),
('UNIDAD EDUCATIVA "ECONOMISTA ABDON CALDERÓN"', '17H01373', 'Fiscal'),
('UNIDAD EDUCATIVA "GALO VELA ÁLVAREZ"', '17H00413', 'Fiscal'),
('UNIDAD EDUCATIVA "ISABEL ROBALINO"', '17H04529', 'Fiscal'),
('UNIDAD EDUCATIVA "JORGE MANTILLA ORTEGA"', '17H01377', 'Fiscal'),
('UNIDAD EDUCATIVA "LUIS ENRIQUE RAZA BOLAÑOS"', '17H01234', 'Fiscal'),
('UNIDAD EDUCATIVA "MEJÍA D7"', '17H00438', 'Fiscal'),
('UNIDAD EDUCATIVA "NUEVA AURORA"', '17H01291', 'Fiscal'),
('UNIDAD EDUCATIVA “15 DE DICIEMBRE”', '17H01035', 'Fiscal'),
('UNIDAD EDUCATIVA “JULIO TOBAR DONOSO”', '17H00445', 'Fiscal'),
('UNIDAD EDUCATIVA COMUNITARIA INTERCULTURAL BILINGÜE "TINKU YACHAY"', '17B00037', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "BICENTENARIO D7 VESPERTINO"', '17H01044', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "LUIS FELIPE BORJA DEL ALCÁZAR"', '17H00384', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL AIDA GALLEGOS  DE MONCAYO', '17H00561', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL ARTURO BORJA', '17H00379', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL CAMINO DEL INCA', '17H01055', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL FEDERICO GARCÍA LORCA', '17H00449', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL PRIMICIAS DE LA CULTURA DE QUITO', '17H00417', 'Fiscal'),
('UNIDAD EDUCATIVA MANUELA SAENZ DE AIZPURU D7', '17H02894', 'Fiscal'),
('UNIDAD EDUCATIVA "13 DE ABRIL"', '17H01252', 'Fiscal'),
('UNIDAD EDUCATIVA "24 DE MAYO"', '17H00441', 'Fiscal'),
('UNIDAD EDUCATIVA "CINCO DE JUNIO"', '17H00910', 'Fiscal'),
('UNIDAD EDUCATIVA "CONSEJO PROVINCIAL DE PICHINCHA"', '17H01128', 'Fiscal'),
('UNIDAD EDUCATIVA "DR. EMILIO UZCATEGUI"', '17H00483', 'Fiscal'),
('UNIDAD EDUCATIVA "NICOLÁS GUILLÉN"', '17H01268', 'Fiscal'),
('UNIDAD EDUCATIVA "QUITO SUR"', '17H00593', 'Fiscal'),
('UNIDAD EDUCATIVA "QUITO"', '17H01188', 'Fiscal'),
('UNIDAD EDUCATIVA "SUCRE"', '17H00921', 'Fiscal'),
('UNIDAD EDUCATIVA "VICENTE ROCAFUERTE"', '17H01206', 'Fiscal'),
('UNIDAD EDUCATIVA “11 DE MARZO”', '17H01251', 'Fiscal'),
('UNIDAD EDUCATIVA “AMAZONAS”', '17H00931', 'Fiscal'),
('UNIDAD EDUCATIVA “ANDRÉS F. CÓRDOVA”', '17H00559', 'Fiscal'),
('UNIDAD EDUCATIVA “BENITO JUÁREZ”', '17H00570', 'Fiscal'),
('UNIDAD EDUCATIVA “CAPITÁN ALFONSO ARROYO AGUIRRE”', '17H01241', 'Fiscal'),
('UNIDAD EDUCATIVA “GONZALO ZALDUMBIDE”', '17H01461', 'Fiscal'),
('UNIDAD EDUCATIVA “JUAN PÍO MONTUFAR”', '17H01225', 'Fiscal'),
('UNIDAD EDUCATIVA “MIGUEL DE SANTIAGO”', '17H01472', 'Fiscal'),
('UNIDAD EDUCATIVA “SEIS DE DICIEMBRE”', '17H01222', 'Fiscal'),
('UNIDAD EDUCATIVA DE EDUCACIÓN ESPECIALIZADA FISCAL “DR. ROBERTO DÍAZ RODRÍGUEZ”', '17H01207', 'Fiscal'),
('UNIDAD EDUCATIVA ESPECIALIZADA DR. RODRIGO CRESPO TORAL', '17H00353', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "FORESTAL"', '17H01193', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "JOSÉ DE LA CUADRA"', '17H00624', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL TARQUI', '17H01254', 'Fiscal'),
('UNIDAD EDUCATIVA “TUMBACO”', '17H02108', 'Fiscal'),
('UNIDAD EDUCATIVA “TRES DE DICIEMBRE”', '17H01758', 'Fiscal'),
('UNIDAD EDUCATIVA FISCOMISIONAL "SANTA CATALINA LABOURÉ"', '17H02144', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCAL "PEDRO BOUGUER"', '17H02133', 'Fiscal'),
('UNIDAD EDUCATIVA FISCOMISIONAL "SAN IGNACIO DE LOYOLA"', '17H01761', 'Fiscomisional'),
('UNIDAD EDUCATIVA COMUNITARIA INTERCULTURAL BILINGÜE AYNI PACHA', '17B00027', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCAL "24 DE JULIO"', '17H02107', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL VÍCTOR MANUEL PEÑAHERRERA', '17H02109', 'Fiscal'),
('UNIDAD EDUCATIVA "PROFESOR PEDRO ECHEVERRIA TERÁN"', '17H01752', 'Fiscal'),
('UNIDAD EDUCATIVA “LEONARDO MALDONADO PÉREZ”', '17H02015', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "EDUARDO SALAZAR GÓMEZ"', '17H01903', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "DR. ARTURO FREIRE"', '17H02079', 'Fiscal'),
('UNIDAD EDUCATIVA "YARUQUÍ"', '17H02143', 'Fiscal'),
('UNIDAD EDUCATIVA "DR. MIGUEL ÁNGEL ZAMBRANO"', '17H00976', 'Fiscal'),
('UNIDAD EDUCATIVA “GABRIELA MISTRAL”', '17H00812', 'Fiscal'),
('UNIDAD EDUCATIVA COMUNITARIA INTERCULTURAL BILINGÜE “AMAWTA RIKCHARI”', '17B00058', 'Fiscal'),
('UNIDAD EDUCATIVA FEDERICO GONZALEZ SUAREZ', '17H00823', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "DANIEL ENRIQUE PROAÑO"', '17H00597', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "DARIO GUEVARA MAYORGA"', '17H00771', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "DIEZ  DE AGOSTO"', '17H00799', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "EUGENIO ESPEJO"', '17H00890', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "MANUEL CÓRDOVA GALARZA"', '17H00647', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "MEJIA"', '17H00864', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "PICHINCHA"', '17H00805', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "RAFAEL LARREA ANDRADE"', '17H00826', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "SANTIAGO DE GUAYAQUIL"', '17H00662', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "SIMON BOLIVAR"', '17H00791', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL PCEI GENERAL RUMIÑAHUI', '17H00755', 'Fiscal'),
('UNIDAD EDUCATIVA FISCOMISIONAL "DON BOSCO"', '17H00705', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCOMISIONAL “VIRGEN DEL CONSUELO”', '17H00423', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCOMISIONAL PCEI DE PICHINCHA', '17H02810', 'Fiscomisional'),
('UNIDAD EDUCATIVA RÍO PACHIJAL', '17H01880', 'Fiscal'),
('UNIDAD EDUCATIVA 24 DE JULIO', '17H01877', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL ALFREDO PÉREZ CHIRIBOGA', '17H01790', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL SAN FRANCISCO DE QUITO', '17H01851', 'Fiscal'),
('UNIDAD EDUCATIVA NANEGAL', '17H01843', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL TENIENTE HUGO ORTÍZ', '17H01848', 'Fiscal'),
('UNIDAD EDUCATIVA NANEGALITO', '17H01857', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL " EQUINOCCIO SAN ANTONIO"', '17H02044', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL “ALEXANDER VON HUMBOLDT"', '17H02043', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL NONO', '17H01869', 'Fiscal'),
('UNIDAD EDUCATIVA COMUNITARIA INTERCULTURAL BILINGÜE MUSHUK YACHAY', '17B00005', 'Fiscal'),
('UNIDAD EDUCATIVA PISULI', '17H00183', 'Fiscal'),
('UNIDAD EDUCATIVA "MANUEL ABAD"', '17H00210', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL 11 DE OCTUBRE', '17H00178', 'Fiscal'),
('UNIDAD EDUCATIVA TARQUINO IDROBO', '17H00181', 'Fiscal'),
('UNIDAD EDUCATIVA  "DR. MANUEL BENJAMÍN CARRIÓN MORA"', '17H01083', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "ATANASIO VITERI"', '17H00127', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL “MITAD DEL MUNDO”', '17H02050', 'Fiscal'),
('UNIDAD EDUCATIVA "AVIACIÓN CIVIL"', '17H01337', 'Fiscal'),
('UNIDAD EDUCATIVA ARUPOS', '17H04413', 'Fiscal'),
('UNIDAD EDUCATIVA FAE Nº 1', '17H01367', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL “ALFONSO LASO BERMEO”', '17H00018', 'Fiscal'),
('UNIDAD EDUCATIVA "CENTRAL TÉCNICO"', '17H00314', 'Fiscal'),
('UNIDAD EDUCATIVA JUAN LARREA HOLGUÍN', '17H04490', 'Fiscal'),
('UNIDAD EDUCATIVA "GRAN BRETAÑA"', '17H00883', 'Fiscal'),
('UNIDAD EDUCATIVA "SAN FRANCISCO DE QUITO"', '17H01010', 'Fiscal'),
('UNIDAD EDUCATIVA PCEI FISCAL SALAMANCA', '17H00215', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL “VENCEDORES”', '17H01354', 'Fiscal'),
('CONSERVATORIO NACIONAL DE MÚSICA', '17H03201', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL NUMA POMPILIO LLONA', '17H00017', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL RAUL ANDRADE', '17H00281', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL DR JOSE MARIA VELASCO IBARRA', '17H00581', 'Fiscal'),
('UNIDAD EDUCATIVA “ROSARIO GONZÁLEZ DE MURILLO”', '17H01350', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL “24 DE MAYO”', '17H00316', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL "LUCIANO ANDRADE MARÍN"', '17H00121', 'Fiscal'),
('UNIDAD EDUCATIVA "HIPATIA CÁRDENAS DE BUSTAMANTE"', '17H00133', 'Fiscal'),
('UNIDAD EDUCATIVA “ELOY ALFARO”', '17H00120', 'Fiscal'),
('UNIDAD EDUCATIVA "GRAN COLOMBIA"', '17H00016', 'Fiscal'),
('UNIDAD EDUCATIVA "LUIS NAPOLEÓN DILLON"', '17H00014', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL DOMINGO FAUSTINO SARMIENTO', '17H01006', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL MAYOR GALO MOLINA', '17H00206', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL CAMILO PONCE ENRIQUEZ', '17H00124', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL LOS SHYRIS', '17H00302', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL PEDRO LUIS CALERO', '17H02147', 'Fiscal'),
('UNIDAD EDUCATIVA "ONCE DE FEBRERO"', '17H01862', 'Fiscal'),
('UNIDAD EDUCATIVA ESPECIALIZADA PARA SORDOS MIGUEL MORENO ESPINOSA', '17H01341', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL JUAN MONTALVO', '17H02853', 'Fiscal'),
('UNIDAD EDUCATIVA REPUBLICA DE BOLIVIA', '17H01012', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL “CARLOS ZAMBRANO OREJUELA”', '17H00786', 'Fiscal'),
('CONSERVATORIO “LUIS HUMBERTO SALGADO TORRES"', '17H01489', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL GENERAL PÍNTAG', '17H01943', 'Fiscal'),
('UNIDAD EDUCATIVA FISCOMISIONAL "FE Y ALEGRÍA LA DOLOROSA"', '17H01567', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCOMISIONAL ELENA ENRIQUEZ', '17H01621', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCAL RÉPLICA JUAN PÍO MONTÚFAR', '17H01565', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL LUXEMBURGO', '17H01551', 'Fiscal'),
('UNIDAD EDUCATIVA "CLUB ÁRABE ECUATORIANO"', '17H01573', 'Fiscal'),
('UNIDAD EDUCATIVA LLANO CHICO', '17H01826', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL BRETHREN', '17H01611', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL PABLO MUÑOZ VEGA', '17H01606', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL ING JUAN SUAREZ CHACON', '17H01631', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL JACINTO COLLAHUAZO', '17H01830', 'Fiscal'),
('UNIDAD EDUCATIVA ABDON CALDERÓN', '17H01571', 'Fiscal'),
('UNIDAD EDUCATIVA NICOLAS JIMENEZ', '17H01550', 'Fiscal'),
('UNIDAD EDUCATIVA TARQUI', '17H01556', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL ALFREDO CISNEROS', '17H01639', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL CALDERÓN 2', '17H01566', 'Fiscal'),
('UNIDAD EDUCATIVA COMUNITARIA INTERCULTURAL BILINGÜE FISCAL KITUKARA', '17B00095', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL ESPAÑA', '17H01612', 'Fiscal'),
('U.E.C.I.B. GUARDIANA DE LA LENGUA Y DE LOS SABERES MUSHUK PAKARI', '17B00098', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL ATAHUALPA', '17H01619', 'Fiscal'),
('UNIDAD EDUCATIVA FISCOMISIONAL "JOSÉ MARÍA VÉLAZ, S.J. – IRFEYAL – EXTENSIÓN 42-D- LUZ Y VIDA"', '17H03570', 'Fiscomisional'),
('UNIDAD EDUCATIVA GUARDIANA DE LOS SABERES "LUZ Y VIDA"', '17H01558', 'Fiscal'),
('UNIDAD EDUCATIVA FISCAL “RICARDO ÁLVAREZ MANTILLA"', '17H01811', 'Fiscal'),
('UNIDAD EDUCATIVA "JOSÉ MARÍA VARGAS"', '17H01714', 'Fiscal'),
('UNIDAD EDUCATIVA FISCOMISIONAL “GLEND SIDE” FE Y ALEGRÍA', '17H01957', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCOMISIONAL “SANTA TERESITA DEL VALLE” FE Y ALEGRÍA"', '17H01706', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCOMISIONAL “FRATERNIDAD Y SERVICIO” FE Y ALEGRÍA', '17H01926', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCOMISIONAL “MERCEDES DE JESÚS MOLINA”', '17H02850', 'Fiscomisional'),
('UNIDAD EDUCATIVA FISCAL ATAHUALPA', '17H01475', 'Fiscal'),
('UNIDAD EDUCATIVA “JOSÉ MARÍA VELASCO IBARRA”', '17H01794', 'Fiscal');
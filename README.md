# ORTRAT ERP 

## ESPECIFICACIONES TÉCNICAS
---
- Este proyecto se encuentra realizado con:
  - [Dolibarr](https://www.dolibarr.es/) V.14.0.2
  - PHP 7.4
  - Composer

## INSTALACIÓN Y PUESTA EN MARCHA
---
Para poder tener el proyecto listo deberás realizar los siguientes pasos:

1.  Empezar importando una base de datos que se te facilitará **AVISO, ES PROBABLE QUE DADO EL TAMAÑO DE LA BBDD DEBAS CONFIGURAR TU FICHERO PHP.INI ALOJADO EN TU XAMPP, PARA ELLO MIRA EL ARCHIVO EN EL DIRECTORIO RAÍZ DEL PROYECTO PHP.INI.EXAMPLE**

2. Dirígete al fichero conf/conf.php y rellena los datos de las variables necesarias, puedes fijarte en el ejemplo ya existente dentro de la carpeta

3. Deberás usar el siguiente comando para añadir las dependencias del proyecto, a nivel de raíz 
```
composer install
```

4. Aconsejable tener la extensión de PHP Intelephense en VSCODE

## EXPLICACIÓN DEL PROYECTO
---
- El proyecto es un ERP para gestionar los proyectos, inventario y demás necesidades de la compañía [Ortrat](http://ortrat.es/es/)
- Actualmente, el proyecto se encuentra dividido por varios módulos propios de Dolibarr y propios desarrollados por Deltanet, los propios se encontrarán dentro de la carpeta custom
- Dentro de los distintos módulos se encuentran repartidos las estructuras de archivos estándar index,list,card, etc... haciendo mención a su sección correspondiente
- A la hora de trabajar dentro de los distintos módulos es aconsejable seguir buenas prácticas para evitar el código espagueti, ya que al estar desarrollado en PHP puro se tiende a aglutinar con facilidad.
- El propio Dolibarr ya cuenta con su propia API para poder realizar funciones típicas CRUD y otras para generar formularios y componentes visuales

### PRIMEROS PASOS
---
- Una vez tengas todo configurado, ve a la página de login e introduce el siguiente usuario y contraseña **khonos / DELTAnet22***
- Te encontrarás en la página principal donde tendrás acceso a los distintos módulos con sus enlaces tanto en la parte superior como en los iconos o menú lateral 
- En el menú lateral izquierdo verás la sección de configuración donde se encontraran múltiples opciones, las más importantes son:
  - Módulos
  - Menú
  - Traducción
- Trata de explorar los distintos módulos para ver la lógica de negocio y los distintos procesos llevados a cabo  


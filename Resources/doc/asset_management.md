# Управление ресурасми

*Все команды необходимо выполнять в корневом каталоге бандла.*

Для управления ресурсами используются приложения Bower и Gulp, построенные на стеке Node.js. Для установки выполняем

```shell
$ npm install
```

## Добавление сторонних ресурсов

Для управления сторонними ресурсами используется Bower.

Ищем нужный пакет:

```shell
$ node_modules/bower/bin/bower search whatever
```

Устанавливаем его:

```shell
$ node_modules/bower/bin/bower install whatever --save
```

Пакет будет установлен в "Resources/public/vendor". Добавляем скачанный пакет в репозиторий и коммитим его. Коммитим
 изменения в файле bower.json (обновленный список зависимостей), расположенном в корне бандла.
 
В файле gulpfile.js, также находящемся в корне, указываем, какие компоненты установленного пакета необходимо использовать
 при сборке.

## Сборка ресурсов

Сборка осуществляется с использованием Gulp.

В процессе разработки запускаем процесс, который будет следить за изменениями в файлах и пересобирать dev-билды:

```shell
$ node_modules/gulp/bin/gulp.js build && node_modules/gulp/bin/gulp.js watch
```

После внесения всех требуемых изменений собираем ресурсы:

```
$ node_modules/gulp/bin/gulp.js build-prod 
```

Коммитим их, о чем заботливо напомнит сценарий сборки.

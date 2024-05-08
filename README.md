Данное приложение создано для удобства управления kv для греческих серверов. В нем реализованы массовые функции.

### Workbench
Для работы приложением нужно:
1) скопировать содержание файла .env.example в .env и актуализировать его (токен приведен только для примера)
2) подтянуть вендоры:
```shell
composer install 
```
3) (не обязательно, название окружений можно передать через опции в ручную) Далее для корректной работы функционала получения списка греческих серверов из консула нужно установить его cli приложение:
```shell
brew install consul
export CONSUL_HTTP_SSL=true
export CONSUL_HTTP_ADDR='****'
```

Далее можно посмотреть список функционала приложения выполнив из корня
```shell
php index.php
```



### Примеры

Список всех греческих приставок:
```shell
php index.php consul:list
```

Создание нового секрета или если надо перетереть все старые ключи
```shell
php index.php vault:add-key new-test-service keyName someVal -c
```

Добавить ключ=значение в секрет "new-test-service"
```shell
php index.php vault:add-key new-test-service keyName someVal
```

Переименовать ключ в секрете "new-test-service"
```shell
php index.php vault:rename-key new-test-service keyName newKeyName
```

Удалить ключ в секрете "new-test-service"
```shell
php index.php vault:remove-key new-test-service keyName
```

Так же у всех команд есть:
1) режим дебага -d (посмотреть какие действия и где будут сделаны без сохранения)
2) греческие сервера можно передавать через -e в таком случае действия будут проводится только с выбранными окружениями а не со всеми доступными в консуле
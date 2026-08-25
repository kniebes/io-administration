# io-administration 

## Deployment

`APP_SECRET` ist in `.env` bewusst leer. Werte stehen nur in den eingecheckten
`.env.dev` und `.env.test`, die ausschliesslich fuer Entwicklung und Tests gelten.

Fuer die Produktion muss `APP_SECRET` deshalb gesetzt werden, entweder als echte
Umgebungsvariable oder ueber `composer dump-env prod`. Bleibt es leer, sind
signierte URIs und alles weitere, was auf dem Secret aufbaut, vorhersagbar.

## Benutzer anlegen

```bash
ddev console app:user:create <benutzername>            # fragt das Passwort verdeckt ab
ddev console app:user:create <benutzername> --admin    # zusaetzlich mit ROLE_ADMIN
```

Das Passwort laesst sich auch als zweites Argument uebergeben, landet dann aber in
der Shell-History und in der Prozessliste. Verlangt werden mindestens 12 Zeichen
und eine ausreichende Passwortstaerke.

# Kubera

OData-leesacties (pagina, `project_workorders_batch.php` en elk CLI-proces dat `web/odata.php` laadt) gaan via Mímir wanneer `$mimirApi` in `web/auth.php` staat.

Laat de Business Central-gegevens in datzelfde bestand staan, naast `$mimirApi`: `$baseUrl`, `$environment`, `$auth_list` en `$auth`. Valt Mímir uit (verbinding/timeout, non-2xx, ongeldige JSON of een Mímir-fout), dan haalt Kubera dezelfde data via het oude directe BC-pad op, inclusief de lokale odata-filecache, en slaat Mímir voor de rest van dat PHP-proces over. Zonder die BC-gegevens gaat de oorspronkelijke Mímir-fout door. Zonder `$mimirApi` blijft alleen het directe BC-pad actief.

Die fallback hoort in `web/odata.php`. Tim Falken keurde die uitzondering op de regel “`web/odata.php` niet aanpassen” goed op 2026-09-28.

Er is geen aparte nightly-job in deze repo. `project_workorders_batch.php` is het HTTP-endpoint van de pagina; een CLI-script dat `odata.php` include't gebruikt dezelfde fallback (Mímir-timeout 600s op CLI, ongeveer 90s bij een webrequest, connect-timeout 10s).

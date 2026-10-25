<?php
// No public home page: the site root just opens the admin panel.
header('Location: admin/', true, 302);
exit;

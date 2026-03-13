Rip free Framer sites dynamically to attach a custom domain to them. No updates required, files served over Framer CDN, on demand calls. Works with tracking.

# How it works

Simply replace **$baseUrl** in **index.php** with your free framer subdomain in order to allow incoming traffic to fetch Framer files and convert it.
This script was tested and used in a production environment with php 8. Remember to configure custom htaccess rules in case you don't use Apache as a webserver.

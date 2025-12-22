<?php

declare(strict_types=1);

namespace App\Libraries\Swagger;

class HtmlSwagger
{
    public static function get(string $json): string
    {
        return '<!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="utf-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1" />
                    <title>API Documentation</title>
                    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
                </head>
                <body>
                    <div id="swagger-ui"></div>
                    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
                    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
                    <script>
                        let json = ' . $json . '
                    </script>
                    <script>
                        window.onload = () => {
                            window.ui = SwaggerUIBundle({
                                spec: json,
                                dom_id: "#swagger-ui",
                                presets: [
                                    SwaggerUIBundle.presets.apis,
                                    SwaggerUIStandalonePreset
                                ],
                                layout: "StandaloneLayout",
                            });
                        };
                    </script>
                </body>
            </html>';
    }
}
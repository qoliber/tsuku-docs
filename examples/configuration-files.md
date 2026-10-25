# Configuration Files

Generate YAML, TOML, INI, and other configuration file formats from your data.

Indentation matters in most of these formats, and Tsuku's [whitespace control](/guide/whitespace-control) is built for it: a line that holds only directives (`@if(...)`, `@for(...)`, `@end`, ...) produces no output at all, so the directives can be indented like the configuration around them, while content lines keep their own indentation.

## YAML Configuration

### Application Config (YAML)

```php
use Qoliber\Tsuku\Tsuku;

$tsuku = new Tsuku();

$template = 'app:
  name: @trim(app.name)
  version: {app.version}
  environment: {app.env}
  debug: @?{app.debug "true" : "false"}

database:
  host: {db.host}
  port: {db.port}
  name: {db.database}
  username: {db.username}
  password: {db.password}
  charset: {db.charset}

cache:
  driver: {cache.driver}
  ttl: {cache.ttl}
  prefix: {cache.prefix}

services:
@for(services as service)
  - name: {service.name}
    enabled: @?{service.enabled "true" : "false"}
    config:
      endpoint: {service.endpoint}
      timeout: {service.timeout}
@end';

$data = [
    'app' => [
        'name' => 'My Application',
        'version' => '1.0.0',
        'env' => 'production',
        'debug' => false
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'myapp',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4'
    ],
    'cache' => [
        'driver' => 'redis',
        'ttl' => 3600,
        'prefix' => 'app_'
    ],
    'services' => [
        ['name' => 'api', 'enabled' => true, 'endpoint' => 'https://api.example.com', 'timeout' => 30],
        ['name' => 'worker', 'enabled' => false, 'endpoint' => 'https://worker.example.com', 'timeout' => 60],
    ]
];

file_put_contents('config.yaml', $tsuku->process($template, $data));
```

**Output (config.yaml):**
```yaml
app:
  name: My Application
  version: 1.0.0
  environment: production
  debug: false

database:
  host: localhost
  port: 3306
  name: myapp
  username: root
  password: secret
  charset: utf8mb4

cache:
  driver: redis
  ttl: 3600
  prefix: app_

services:
  - name: api
    enabled: true
    config:
      endpoint: https://api.example.com
      timeout: 30
  - name: worker
    enabled: false
    config:
      endpoint: https://worker.example.com
      timeout: 60
```

::: tip Booleans
Map a PHP boolean to `true`/`false` (or `On`/`Off`, `yes`/`no`) with an inline condition: `@?{app.debug "true" : "false"}`. Tsuku has no boolean literals - `true` inside a template is a variable name - so `@match(app.debug) @case(true)` or `@case(1, true)` cannot tell `true` from `false`.
:::

## Docker Compose

### docker-compose.yml

```php
$template = 'version: "{version}"

services:
@for(services as service)
  {service.name}:
    image: {service.image}
    @if(service.build)
    build:
      context: {service.build.context}
      dockerfile: {service.build.dockerfile}
    @end
    @if(service.ports)
    ports:
      @for(service.ports as port)
      - "{port}"
      @end
    @end
    @if(service.environment)
    environment:
      @for(service.environment as env)
      - {env.key}={env.value}
      @end
    @end
    @if(service.volumes)
    volumes:
      @for(service.volumes as volume)
      - {volume}
      @end
    @end
    @if(service.depends_on)
    depends_on:
      @for(service.depends_on as dep)
      - {dep}
      @end
    @end

@end';

$data = [
    'version' => '3.8',
    'services' => [
        [
            'name' => 'web',
            'image' => 'php:8.1-fpm',
            'build' => [
                'context' => '.',
                'dockerfile' => 'Dockerfile'
            ],
            'ports' => ['8080:80'],
            'environment' => [
                ['key' => 'APP_ENV', 'value' => 'production'],
                ['key' => 'DB_HOST', 'value' => 'mysql']
            ],
            'volumes' => [
                './app:/var/www/html'
            ],
            'depends_on' => ['mysql', 'redis']
        ],
        [
            'name' => 'mysql',
            'image' => 'mysql:8.0',
            'ports' => ['3306:3306'],
            'environment' => [
                ['key' => 'MYSQL_ROOT_PASSWORD', 'value' => 'secret'],
                ['key' => 'MYSQL_DATABASE', 'value' => 'myapp']
            ],
            'volumes' => [
                'mysql_data:/var/lib/mysql'
            ],
            'depends_on' => null
        ],
        [
            'name' => 'redis',
            'image' => 'redis:7-alpine',
            'ports' => ['6379:6379'],
            'environment' => null,
            'volumes' => null,
            'depends_on' => null
        ]
    ]
];

file_put_contents('docker-compose.yml', $tsuku->process($template, $data));
```

**Output (docker-compose.yml):**
```yaml
version: "3.8"

services:
  web:
    image: php:8.1-fpm
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
    volumes:
      - ./app:/var/www/html
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=secret
      - MYSQL_DATABASE=myapp
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

```

The indented `@if`/`@for`/`@end` lines vanish completely, indentation and line break included; only the content lines (with their own indentation) and the blank line before the closing `@end` reach the output. Sections whose value is `null` or an empty array are skipped.

## TOML Configuration

### Cargo.toml (Rust)

```php
$template = '[package]
name = "{package.name}"
version = "{package.version}"
edition = "{package.edition}"
authors = [@for(package.authors as author, index)@if(index > 0), @end"{author.name}"@end]

[dependencies]
@for(dependencies as dep)
{dep.name} = "@default(dep.version, "*")"
@end

[[bin]]
name = "{binary.name}"
path = "{binary.path}"';

$data = [
    'package' => [
        'name' => 'my-app',
        'version' => '0.1.0',
        'edition' => '2021',
        'authors' => [
            ['name' => 'John Doe <john@example.com>'],
            ['name' => 'Jane Smith <jane@example.com>']
        ]
    ],
    'dependencies' => [
        ['name' => 'serde', 'version' => '1.0'],
        ['name' => 'tokio', 'version' => '1.0'],
        ['name' => 'anyhow', 'version' => ''],
    ],
    'binary' => [
        'name' => 'my-app',
        'path' => 'src/main.rs'
    ]
];

file_put_contents('Cargo.toml', $tsuku->process($template, $data));
```

**Output (Cargo.toml):**
```toml
[package]
name = "my-app"
version = "0.1.0"
edition = "2021"
authors = ["John Doe <john@example.com>", "Jane Smith <jane@example.com>"]

[dependencies]
serde = "1.0"
tokio = "1.0"
anyhow = "*"

[[bin]]
name = "my-app"
path = "src/main.rs"
```

TOML arrays need each string quoted, so the authors are written with a loop and a separator in front of every name except the first (`@if(index > 0), @end`). `@default(dep.version, "*")` fills in `*` for the empty version.

## INI Files

### PHP Configuration (php.ini)

```php
$template = '[PHP]
engine = @?{php.engine "On" : "Off"}
short_open_tag = @?{php.short_open_tag "On" : "Off"}
max_execution_time = {php.max_execution_time}
max_input_time = {php.max_input_time}
memory_limit = {php.memory_limit}M
error_reporting = E_ALL
display_errors = @?{php.display_errors "On" : "Off"}
log_errors = @?{php.log_errors "On" : "Off"}
error_log = {php.error_log}

[Date]
date.timezone = {date.timezone}

[Session]
session.save_handler = {session.save_handler}
session.save_path = {session.save_path}
session.gc_maxlifetime = {session.gc_maxlifetime}

@for(extensions as ext)
extension={ext}
@end';

$data = [
    'php' => [
        'engine' => true,
        'short_open_tag' => false,
        'max_execution_time' => 30,
        'max_input_time' => 60,
        'memory_limit' => 128,
        'display_errors' => false,
        'log_errors' => true,
        'error_log' => '/var/log/php_errors.log'
    ],
    'date' => [
        'timezone' => 'UTC'
    ],
    'session' => [
        'save_handler' => 'redis',
        'save_path' => 'tcp://127.0.0.1:6379',
        'gc_maxlifetime' => 1440
    ],
    'extensions' => ['mysqli', 'pdo_mysql', 'redis', 'opcache']
];

file_put_contents('php.ini', $tsuku->process($template, $data));
```

**Output (php.ini):**
```ini
[PHP]
engine = On
short_open_tag = Off
max_execution_time = 30
max_input_time = 60
memory_limit = 128M
error_reporting = E_ALL
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

[Date]
date.timezone = UTC

[Session]
session.save_handler = redis
session.save_path = tcp://127.0.0.1:6379
session.gc_maxlifetime = 1440

extension=mysqli
extension=pdo_mysql
extension=redis
extension=opcache
```

## Environment Files

### .env File

```php
$template = '# Application
APP_NAME=@upper(app.name)
APP_ENV={app.env}
APP_DEBUG=@?{app.debug "true" : "false"}
APP_URL={app.url}

# Database
DB_CONNECTION={db.connection}
DB_HOST={db.host}
DB_PORT={db.port}
DB_DATABASE={db.database}
DB_USERNAME={db.username}
DB_PASSWORD={db.password}

# Cache
CACHE_DRIVER={cache.driver}
@match(cache.driver)
@case("redis")
REDIS_HOST={cache.redis.host}
REDIS_PORT={cache.redis.port}
REDIS_PASSWORD={cache.redis.password}
@end

# Mail
MAIL_MAILER={mail.mailer}
MAIL_HOST={mail.host}
MAIL_PORT={mail.port}
MAIL_USERNAME={mail.username}
MAIL_PASSWORD={mail.password}
MAIL_FROM_ADDRESS={mail.from.address}
MAIL_FROM_NAME="{mail.from.name}"';

$data = [
    'app' => [
        'name' => 'My Application',
        'env' => 'production',
        'debug' => false,
        'url' => 'https://example.com'
    ],
    'db' => [
        'connection' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'myapp',
        'username' => 'root',
        'password' => 'secret123'
    ],
    'cache' => [
        'driver' => 'redis',
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null
        ]
    ],
    'mail' => [
        'mailer' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'noreply@example.com',
        'password' => 'mail_secret',
        'from' => [
            'address' => 'noreply@example.com',
            'name' => 'My Application'
        ]
    ]
];

file_put_contents('.env', $tsuku->process($template, $data));
```

**Output (.env):**
```ini
# Application
APP_NAME=MY APPLICATION
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=secret123

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD=mail_secret
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="My Application"
```

The `@match`/`@case`/`@end` lines produce no output, and a `null` value (`REDIS_PASSWORD`) renders as an empty string. The email addresses come from the data; an `@` typed into the template text itself would have to be written `@@` (see [Escapes & Special Characters](/guide/escaping)).

## Kubernetes Configuration

### Deployment YAML

```php
$template = 'apiVersion: apps/v1
kind: Deployment
metadata:
  name: {deployment.name}
  namespace: {deployment.namespace}
  labels:
    @for(deployment.labels as label)
    {label.key}: {label.value}
    @end
spec:
  replicas: {deployment.replicas}
  selector:
    matchLabels:
      @for(deployment.selector as label)
      {label.key}: {label.value}
      @end
  template:
    metadata:
      labels:
        @for(deployment.template.labels as label)
        {label.key}: {label.value}
        @end
    spec:
      containers:
      @for(deployment.containers as container)
      - name: {container.name}
        image: {container.image}
        @if(container.ports)
        ports:
        @for(container.ports as port)
        - containerPort: {port.containerPort}
          protocol: {port.protocol}
        @end
        @end
        @if(container.env)
        env:
        @for(container.env as env)
        - name: {env.name}
          value: "{env.value}"
        @end
        @end
        @if(container.resources)
        resources:
          limits:
            cpu: {container.resources.limits.cpu}
            memory: {container.resources.limits.memory}
          requests:
            cpu: {container.resources.requests.cpu}
            memory: {container.resources.requests.memory}
        @end
      @end';

$data = [
    'deployment' => [
        'name' => 'myapp',
        'namespace' => 'production',
        'replicas' => 3,
        'labels' => [
            ['key' => 'app', 'value' => 'myapp'],
            ['key' => 'version', 'value' => 'v1']
        ],
        'selector' => [
            ['key' => 'app', 'value' => 'myapp']
        ],
        'template' => [
            'labels' => [
                ['key' => 'app', 'value' => 'myapp']
            ]
        ],
        'containers' => [
            [
                'name' => 'web',
                'image' => 'myapp:1.0.0',
                'ports' => [
                    ['containerPort' => 80, 'protocol' => 'TCP']
                ],
                'env' => [
                    ['name' => 'APP_ENV', 'value' => 'production'],
                    ['name' => 'DB_HOST', 'value' => 'mysql-service']
                ],
                'resources' => [
                    'limits' => ['cpu' => '500m', 'memory' => '512Mi'],
                    'requests' => ['cpu' => '250m', 'memory' => '256Mi']
                ]
            ]
        ]
    ]
];

file_put_contents('deployment.yaml', $tsuku->process($template, $data));
```

**Output (deployment.yaml):**
```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: myapp
  namespace: production
  labels:
    app: myapp
    version: v1
spec:
  replicas: 3
  selector:
    matchLabels:
      app: myapp
  template:
    metadata:
      labels:
        app: myapp
    spec:
      containers:
      - name: web
        image: myapp:1.0.0
        ports:
        - containerPort: 80
          protocol: TCP
        env:
        - name: APP_ENV
          value: "production"
        - name: DB_HOST
          value: "mysql-service"
        resources:
          limits:
            cpu: 500m
            memory: 512Mi
          requests:
            cpu: 250m
            memory: 256Mi
```

## Apache Configuration

### Virtual Host

```php
$template = '<VirtualHost *:{vhost.port}>
    ServerName {vhost.server_name}
    @if(vhost.server_alias)
    ServerAlias {vhost.server_alias}
    @end
    DocumentRoot {vhost.document_root}

    <Directory {vhost.document_root}>
        Options @join(vhost.directory.options, " ")
        AllowOverride {vhost.directory.allow_override}
        Require all granted
    </Directory>

    @if(vhost.ssl)
    SSLEngine on
    SSLCertificateFile {vhost.ssl.certificate}
    SSLCertificateKeyFile {vhost.ssl.key}

    @end
    ErrorLog {vhost.error_log}
    CustomLog {vhost.access_log} combined
</VirtualHost>';

$data = [
    'vhost' => [
        'port' => 443,
        'server_name' => 'example.com',
        'server_alias' => 'www.example.com',
        'document_root' => '/var/www/example.com/public',
        'directory' => [
            'options' => ['Indexes', 'FollowSymLinks'],
            'allow_override' => 'All'
        ],
        'ssl' => [
            'certificate' => '/etc/ssl/certs/example.com.crt',
            'key' => '/etc/ssl/private/example.com.key'
        ],
        'error_log' => '/var/log/apache2/example.com-error.log',
        'access_log' => '/var/log/apache2/example.com-access.log'
    ]
];

file_put_contents('example.com.conf', $tsuku->process($template, $data));
```

**Output (example.com.conf):**
```apache
<VirtualHost *:443>
    ServerName example.com
    ServerAlias www.example.com
    DocumentRoot /var/www/example.com/public

    <Directory /var/www/example.com/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/example.com.crt
    SSLCertificateKeyFile /etc/ssl/private/example.com.key

    ErrorLog /var/log/apache2/example.com-error.log
    CustomLog /var/log/apache2/example.com-access.log combined
</VirtualHost>
```

The blank line after the SSL block sits inside the `@if`, so a host without SSL does not get two blank lines in a row.

## Nginx Configuration

### Server Block

```php
$template = 'server {
    listen {server.port}@if(server.ssl) ssl http2@end;
    server_name {server.server_name};

    root {server.root};
    index @join(server.index, " ");

    @if(server.ssl)
    ssl_certificate {server.ssl.certificate};
    ssl_certificate_key {server.ssl.key};

    @end
    location / {
        try_files $uri $uri/ ={server.fallback_status};
    }

    @for(server.locations as location)
    location {location.path} {
        @for(location.directives as directive)
        {directive.key} {directive.value};
        @end
    }

    @end
    access_log {server.access_log};
    error_log {server.error_log};
}';

$data = [
    'server' => [
        'port' => 443,
        'ssl' => [
            'certificate' => '/etc/nginx/ssl/example.com.crt',
            'key' => '/etc/nginx/ssl/example.com.key'
        ],
        'server_name' => 'example.com www.example.com',
        'root' => '/var/www/example.com/public',
        'index' => ['index.php', 'index.html'],
        'fallback_status' => 404,
        'locations' => [
            [
                'path' => '~ \\.php$',
                'directives' => [
                    ['key' => 'fastcgi_pass', 'value' => 'unix:/var/run/php/php8.1-fpm.sock'],
                    ['key' => 'fastcgi_index', 'value' => 'index.php'],
                    ['key' => 'include', 'value' => 'fastcgi_params']
                ]
            ]
        ],
        'access_log' => '/var/log/nginx/example.com-access.log',
        'error_log' => '/var/log/nginx/example.com-error.log'
    ]
];

file_put_contents('example.com', $tsuku->process($template, $data));
```

**Output (example.com):**
```nginx
server {
    listen 443 ssl http2;
    server_name example.com www.example.com;

    root /var/www/example.com/public;
    index index.php index.html;

    ssl_certificate /etc/nginx/ssl/example.com.crt;
    ssl_certificate_key /etc/nginx/ssl/example.com.key;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }

    access_log /var/log/nginx/example.com-access.log;
    error_log /var/log/nginx/example.com-error.log;
}
```

`listen {server.port}@if(server.ssl) ssl http2@end;` is an inline block: a single-line branch is printed exactly as written, leading space included.

## Best Practices

### Quote String Values When Needed

```php
$data = ['password' => 'p4ss: #1'];

// YAML needs quotes around values with special characters
echo $tsuku->process('password: "{password}"', $data);  // Good
// Output: password: "p4ss: #1"

echo $tsuku->process('password: {password}', $data);    // Breaks: ": " and " #" are YAML syntax
// Output: password: p4ss: #1
```

Plain double quotes are not enough when a value can itself contain `"` or `\`. YAML 1.2 is a superset of JSON, so `password: @json(password)` gives a correctly escaped string for YAML 1.2 parsers; for other formats, register a [custom function](/guide/custom-functions) that escapes the way the format requires.

### Validate Generated Config

```php
// Validate YAML
$yaml = $tsuku->process($template, $data);
$parsed = yaml_parse($yaml);
if ($parsed === false) {
    throw new \RuntimeException('Invalid YAML');
}
```

### Use Comments for Documentation

```php
$template = '# Database Configuration
# Generated on @date("Y-m-d H:i:s", generated_at)
database:
  host: {db.host}';

$data = ['generated_at' => 1705312800, 'db' => ['host' => 'localhost']];

echo $tsuku->process($template, $data);
// Output:
// # Database Configuration
// # Generated on 2024-01-15 10:00:00
// database:
//   host: localhost
```

`#` is plain text to Tsuku, so the comment lines are copied into the file. Tsuku itself has no comment syntax.

## Next Steps

- [E-commerce →](/examples/ecommerce) - E-commerce specific examples
- [Best Practices →](/guide/best-practices) - Configuration patterns
- [Examples →](/examples/) - More real-world examples

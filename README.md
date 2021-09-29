# ETAAMB
Exercice Technique Appliqué Au Moniteur Belge

Currently in development

## Development
Run a docker instance :
```bash
docker build -t "etaamb" -f ./Dockerfile . && docker run --rm -it --name etaamb etaamb
```

### Agent
ETAAMB comes with a "steward" docker for maintenance operations. It accepts commands such as :
```bash
# Show help
> docker exec -it etaamb_steward run -h
# Configure Database
> docker exec -it etaamb_steward run db_setup
# Attach shell
> docker exec -it etaamb_steward bash
```

## Notes and Documentation
- https://tighten.co/blog/converting-a-legacy-app-to-laravel/


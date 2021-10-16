# ETAAMB Steward
The steward is responsible for maintenance operations on the ETAAMB database, like
crawling data sources, parsing content and precalculating pages.

It runs different Perl or PHP scripts, with read/write access to the database, and
can be automated with cron-like tools.


## Run with docker host
Using the docker host, cron tasks can be run :
```bash

# Get Document ID's
docker exec -it ${STEWARD_DOCKERNAME} /agent/moniteur_import/recupId.pl
# Get Raw Pages
docker exec -it ${STEWARD_DOCKERNAME} /agent/moniteur_import/getRaws.pl

```

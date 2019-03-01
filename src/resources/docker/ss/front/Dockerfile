FROM timbru31/ruby-node:latest

# Install required softwares
RUN apt-get update -qy \
 && apt-get install -qy --no-install-recommends \
    fonts-liberation \
    gconf-service \
    ibpango-1.0-0 \
    libappindicator1 \
    libasound2 \
    libcairo2 \
    libcups2 \
    libdbus-1-3 \
    libexpat1 \
    libfontconfig1 \
    libgcc1 \
    libgconf-2-4 \
    libgdk-pixbuf2.0-0 \
    libglib2.0-0 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libpangocairo-1.0-0 \
    libstdc++6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrandr2 \
    libxrender1 \
    libxss1 \
    libxtst6 \
    locales \
    openssh-client \
    ttf-freefont \
    unzip \
    xdg-utils \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install supervisor
RUN apt-get update -qy && apt-get install -qy --no-install-recommends apt-transport-https supervisor && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Shrink image
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install local binaries wrapper (https://alligator.io/workflow/npx/)
RUN npm install -g npx

# We run supervisor so the container is kept alive so that FE tools can be run using exec
ENTRYPOINT ["/usr/bin/supervisord", "--nodaemon", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

FROM tutum/lamp:latest
RUN rm -fr /app && git clone https://github.com/AcademicTorrents/torrentify.git /app
EXPOSE 80
CMD bash
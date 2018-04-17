#FROM openjdk:8 as builder
#ADD . /srv
#RUN cd /srv/ && ./gradlew assemble


#FROM openjdk:8-jre
#COPY --from=builder /srv/build/libs/oauth-0.0.1-SNAPSHOT.jar /opt/
#WORKDIR /srv
#CMD ["java", "-jar", "/opt/oauth-0.0.1-SNAPSHOT.jar"]

FROM ubuntu
#ADD http://apache.mirrors.tds.net/archiva/2.2.3/binaries/apache-archiva-2.2.3-bin.tar.gz /opt/apache-archiva-2.2.3-bin.tar.gz
ADD apache-archiva-2.2.3-bin.tar.gz /opt/apache-archiva-2.2.3

RUN cd /opt  && ln -s apache-archiva-2.2.3 archiva
	

WORKDIR /srv
CMD ["/opt/archiva/bin/archiva"]

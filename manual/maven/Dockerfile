#FROM openjdk:8 as builder
#ADD . /srv
#RUN cd /srv/ && ./gradlew assemble


#FROM openjdk:8-jre
#COPY --from=builder /srv/build/libs/oauth-0.0.1-SNAPSHOT.jar /opt/
#WORKDIR /srv
#CMD ["java", "-jar", "/opt/oauth-0.0.1-SNAPSHOT.jar"]

FROM openjdk:8-jre-alpine
ADD tiny-maven-proxy.properties /etc/tiny-maven-proxy.properties
ADD https://timboudreau.com/builds/job/tiny-maven-proxy/lastSuccessfulBuild/artifact/tiny-maven-proxy/target/tiny-maven-proxy.jar /opt/tiny-maven-proxy.jar
WORKDIR /srv
CMD ["java", "-jar", "/opt/tiny-maven-proxy.jar"]

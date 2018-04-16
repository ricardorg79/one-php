NAME=maven
REGISTRY=registry.shttps.com/$(NAME)


build: Dockerfile
	docker build --force-rm --no-cache -t $(NAME) .

push:
	docker tag $(NAME) $(REGISTRY)
	docker push $(REGISTRY)

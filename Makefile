all: check examples

check:
	@./tests/launch_tests

examples:
	@./examples/process_examples

.PHONY: check examples

# POD, the PHP preprocessor

POD is a concise language that compiles to PHP. It runs easily on the command line like this:

  $ pod myfile.pod

# Examples

  You can see code examples in the `examples/` directory (thanks, captain obvious).

# Code organisation

the `src/POD/` directory contains `Parser.php` and `Preprocessor.php`. Parser contains generic parsing features that will be extracted in another library, and Preprocessor is the real POD parsing library. Note that it is only 222 lines long, so it is really easy to start hacking on POD :)

# Unit tests

To prevent regressions in the compiler while adding features, unit tests are stored in `tests/unit`. To launch them, use `make check` at the repository's root, or launch an individual file like this:

  $ php tests/unit/Preprocessor.Test.php

If you find a bug, or want to implement a feature, please write a test for it first, to make sure that nobody will break it afterwards.

POD is still a work in progress, but it will get awesome!

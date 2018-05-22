repl:
	clj -Sdeps '{:deps {cider/cider-nrepl {:mvn/version "0.18.0-SNAPSHOT"} proto-repl {:mvn/version "0.3.1"}}}' -e '(require (quote cider-nrepl.main)) (cider-nrepl.main/init ["cider.nrepl/cider-middleware"])'

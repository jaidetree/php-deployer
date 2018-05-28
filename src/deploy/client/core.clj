(ns deploy.client.core
  (:require [clj-http.client :as client]
            [clojure.string :refer [join]]
            [clojure.walk :refer [keywordize-keys]]
            [pem-reader.core :as pem]
            [clojure.pprint :refer [pprint]])
  (:import [java.security SecureRandom Signature]
           [java.util Base64]))

(defn encode64
  "Encode a string into base64 encoding"
  [bytes]
  (.encodeToString (java.util.Base64/getEncoder) bytes))

(defn sign
  "RSA private key signing of a hash. Takes hash as string and private key.
  Returns signature string."
  [hash private-key]
  (encode64
    (let [msg-data (.getBytes hash)
          sig (doto (Signature/getInstance "SHA256withRSA")
                    (.initSign private-key (SecureRandom.))
                    (.update msg-data))]
      (.sign sig))))

(defn now
  "Get the current time in unix seconds. Used to set the timeout.
  Returns a number."
  []
  (quot (System/currentTimeMillis) 1000))

(defn create-hash
  "Create a hash-string to sign with a private key
  Takes a repo string, branch, and timeout.
  Returns a string."
  [repo branch timeout]
  (join "|" [repo branch timeout]))

(defn create-post-body
  "Create the post body params to send to deploy server.
  Takes a map dest-dir, src-dir, branch, repo, private-key.
  Returns a hash-map."
  [{:keys [dest-dir src-dir branch repo private-key]}]
  (let [timeout (+ (now) 5)
        hash (create-hash repo branch timeout)]
    {:dest_dir dest-dir
     :src_dir src-dir
     :branch branch
     :repo repo
     :timeout timeout
     :hash hash
     :signature (sign hash private-key)}))

(defn get-private-key!
  "Create a Java private key instance from a filename.
  Takes a filename string.
  Returns a PrivateKey class instance."
  [filename]
  (->> filename
       (pem/read)
       (pem/private-key)))

(defn request-deploy!
  "Creates a server request to deploy server with deploy request.
  Takes a body hash-map of form params and a server-url string.
  Returns a clj-http.client response."
  [body server-url]
  (client/post server-url {:form-params body
                           :content-type :json
                           :as :json}))

(defn deploy!
  "Creates a deploy request and prints the body
  Takes a server-url string, a private-key PrivateKey instance and keyword
  params like :src-dir :dest-dir :repo and :branch.
  Returns the clj-http response body."
  [server-url private-key {:as args}]
  (-> args
      (assoc :private-key (get-private-key! private-key))
      (create-post-body)
      (request-deploy! server-url)
      :body
      (doto pprint)))

(defn -main
  "Testing function to test direct calls.
  Takes a server-url string a private-key filepath string and additional
  deploy params.
  Returns the clj-http request body."
  [server-url private-key & {:as args}]
  (->> args
       keywordize-keys
       (deploy! server-url private-key)))

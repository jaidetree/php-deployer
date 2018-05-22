(ns client.deploy
  (:require [clj-http.client :as client]
            [clojure.string :refer [join]]
            [clojure.walk :refer [keywordize-keys]]
            [pem-reader.core :as pem])
  (:import [java.security SecureRandom Signature]
           [java.util Base64]))

(defn decode64 [str]
  (.decode (java.util.Base64/getDecoder) str))

(defn encode64 [bytes]
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
  []
  (quot (System/currentTimeMillis) 1000))

(defn create-hash
  [repo branch timeout]
  (join "|" [repo branch timeout]))

(defn create-post-body
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

(defn deploy
  [body server-url]
  (client/post server-url {:form-params body
                           :content-type :json}))

(defn get-private-key
  [filename]
  (->> filename
       (pem/read)
       (pem/private-key)))

(defn -main
  [server-url private-key & {:as args}]
  (clojure.pprint/pprint [server-url private-key args])
  (-> args
      keywordize-keys
      (assoc :private-key (get-private-key private-key))
      (create-post-body)
      (clojure.pprint/pprint)
      (deploy server-url)))

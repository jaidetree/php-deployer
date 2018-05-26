(ns deploy.client.core
  (:require [clj-http.client :as client]
            [clojure.string :refer [join]]
            [clojure.walk :refer [keywordize-keys]]
            [pem-reader.core :as pem]
            [clojure.pprint :refer [pprint]])
  (:import [java.security KeyFactory SecureRandom Signature]
           [java.security.spec PKCS8EncodedKeySpec]
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

(defn request-deploy!
  [body server-url]
  (print (:body
            (client/post server-url {:form-params body
                                     :content-type :json
                                     :accept :json}))))
                                     ; :as :json}))))

; KeyFactory kf = KeyFactory.getInstance("RSA");
; Read privateKeyDerByteArray from DER file.
; KeySpec keySpec = new PKCS8EncodedKeySpec(privateKeyDerByteArray);
; PrivateKey key = kf.generatePrivate(keySpec);

(defn str->private-key
  [key-file]
  (let [kf (KeyFactory/getInstance "RSA")
        key-spec (PKCS8EncodedKeySpec. (.getBytes key-file))]
    (.generatePrivate kf key-spec)))

(defn get-private-key!
  [filename]
  (->> filename
       ; (slurp)
       (pem/read)
       (pem/private-key)))

(defn deploy
  [server-url private-key {:as args}]
  (-> args
      (assoc :private-key (get-private-key! private-key))
      (create-post-body)
      (doto pprint)
      (request-deploy! server-url)))

(defn -main
  [server-url private-key & {:as args}]
  (->> args
       keywordize-keys
       (deploy server-url private-key)))

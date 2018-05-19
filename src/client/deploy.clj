(ns client.deploy
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
    (let [msg-data (.getBytes hash)]
         sig (doto (Signature/getInstance "SHA256withRSA")
                   (.initSign private-key (SecureRandom.))
                   (.update msg-data))
      (.sign sig))))

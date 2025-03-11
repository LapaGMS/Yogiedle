import { useState, useEffect } from "react";

function App() {
  const [quotes, setQuotes] = useState([]);
  const [input, setInput] = useState("");
  const [attempts, setAttempts] = useState(5);
  const [feedback, setFeedback] = useState("");
  const [correctQuote, setCorrectQuote] = useState(null);
  const [suggestions, setSuggestions] = useState([]);

  useEffect(() => {
    fetch("http://localhost:3000/quotes")
      .then((res) => res.json())
      .then((data) => {
        setQuotes(data);
        setCorrectQuote(data[Math.floor(Math.random() * data.length)]);
      });
  }, []);

  const handleChange = (e) => {
    const value = e.target.value;
    setInput(value);

    // Suodatetaan ehdotukset syötteen perusteella
    if (value.length > 0) {
      const filtered = quotes
        .map((q) => q.text)
        .filter((quote) => quote.toLowerCase().includes(value.toLowerCase()));

      setSuggestions(filtered.slice(0, 5)); // Näytetään max 5 ehdotusta
    } else {
      setSuggestions([]);
    }
  };

  const handleSubmit = () => {
    if (attempts === 0) return;

    if (input.toLowerCase() === correctQuote.text.toLowerCase()) {
      setFeedback("✅ Oikein!");
      setSuggestions([]);
    } else {
      setAttempts(attempts - 1);
      setFeedback("❌ Väärin!");
    }
  };

  const handleSuggestionClick = (suggestion) => {
    setInput(suggestion);
    setSuggestions([]);
  };

  return (
    <div className="p-4 text-center">
      <h1 className="text-2xl font-bold">Quote Wordle</h1>
      <p>Arvauksia jäljellä: {attempts}</p>

      <div className="relative">
        <input
          type="text"
          className="border p-2 m-2 w-80"
          value={input}
          onChange={handleChange}
        />
        {suggestions.length > 0 && (
          <ul className="absolute bg-white border w-80 text-left max-h-40 overflow-y-auto">
            {suggestions.map((suggestion, index) => (
              <li
                key={index}
                className="p-2 cursor-pointer hover:bg-gray-200"
                onClick={() => handleSuggestionClick(suggestion)}
              >
                {suggestion}
              </li>
            ))}
          </ul>
        )}
      </div>

      <button className="bg-blue-500 text-white p-2" onClick={handleSubmit}>
        Tarkista
      </button>

      <p className={feedback === "✅ Oikein!" ? "text-green-500" : "text-red-500"}>
        {feedback}
      </p>

      {attempts === 0 && <p className="text-red-500">Oikea vastaus: {correctQuote.text}</p>}
    </div>
  );
}

export default App;
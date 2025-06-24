import openai
from flask import Flask, request, jsonify, session
from flask_cors import CORS
import os
import time
import re
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)
app.secret_key = 'your_secret_key'  # Needed for session
CORS(app, supports_credentials=True)

client = openai.OpenAI(api_key=os.getenv("OPENAI_API_KEY"))

def is_greeting(message):
    greetings = [
        'bonjour', 'salut', 'hello', 'hi', 'coucou', 'bonsoir', 'hey',
        'good morning', 'good afternoon', 'good evening', 'yo', 'hola'
    ]
    msg = message.strip().lower()
    return any(greet in msg and len(msg.split()) <= 4 for greet in greetings)

def extract_crop(message):
    # Improved crop detection: match singular and plural forms
    crops = [
        'tomate', 'carotte', 'pomme de terre', 'laitue', 'oignon', 'poivron',
        'aubergine', 'concombre', 'courgette', 'fraise', 'melon', 'pastèque',
        'maïs', 'blé', 'orge', 'riz', 'soja', 'pois', 'haricot', 'chou', 'épinard',
        'salade', 'radis', 'betterave', 'céleri', 'navet', 'ail', 'persil', 'basilic',
        'citrouille', 'patate douce', 'poireau', 'artichaut', 'brocoli', 'chou-fleur'
    ]
    for crop in crops:
        # Match singular and plural (basic French plural: add 's')
        pattern = rf'\b{crop}(s)?\b'
        if re.search(pattern, message, re.IGNORECASE):
            return crop.lower()
    return None

@app.route('/chatbot_api', methods=['POST'])
def chatbot_api():
    data = request.get_json()
    user_message = data.get('message', '').strip()
    if not user_message:
        return jsonify({'success': False, 'response': 'No message provided.'}), 400

    # Backend greeting detection
    if is_greeting(user_message):
        return jsonify({'success': True, 'response': "Bonjour ! Comment puis-je vous aider avec vos questions agricoles aujourd'hui ?"})

    # Detect crop/topic
    crop = extract_crop(user_message)
    last_crop = session.get('last_crop')

    # If crop changed, reset thread
    if crop and crop != last_crop:
        session.pop('messages', None)
        session['last_crop'] = crop

    # Timeout logic (30 minutes)
    TIMEOUT = 30 * 60
    now = time.time()
    last_active = session.get('last_active', 0)
    if now - last_active > TIMEOUT:
        session.pop('messages', None)
    session['last_active'] = now

    # Initialize or update message history
    if 'messages' not in session:
        session['messages'] = [
            {"role": "system", "content": (
                "You are AgriBot, a friendly and knowledgeable personal assistant for all things farming and agriculture. "
                "You can answer questions about crops, livestock, soil, irrigation, farming tools, and best practices. "
                "Give clear, practical advice in a conversational tone. "
                "Avoid using step-by-step lists or bullet points unless the user specifically asks for steps or a list, or if it is truly necessary. "
                "Otherwise, answer in natural sentences and paragraphs. "
                "Always use the previous user message as context for pronouns like 'les', 'them', etc. "
                "If a question is not related to agriculture, politely ask the user to focus on farming topics. "
                "Respond in the same language as the user's question (French or English). "
                "When the user asks a specific question, answer ONLY that question with a detailed and specific explanation. Do NOT add any general or unrelated information. Do NOT repeat information about tomatoes or any other crop unless it is directly about the requested aspect. If the user asks about irrigation, your answer must ONLY be about irrigation, with no introduction or unrelated context. Be extremely strict about this."
            )}
        ]
    messages = session['messages']

    # Add user message
    messages.append({"role": "user", "content": user_message})

    # Call OpenAI Chat Completions API with error handling
    try:
        response = client.chat.completions.create(
            model="gpt-4o",  # or "gpt-3.5-turbo"
            messages=messages
        )
        assistant_reply = response.choices[0].message.content
    except Exception as e:
        print("OpenAI API error:", e)
        return jsonify({'success': False, 'response': "Erreur du serveur OpenAI : " + str(e)}), 500

    # Add assistant reply to history
    messages.append({"role": "assistant", "content": assistant_reply})
    session['messages'] = messages

    return jsonify({'success': True, 'response': assistant_reply})

@app.route('/reset_thread', methods=['POST'])
def reset_thread():
    session.pop('messages', None)
    session.pop('last_crop', None)
    return jsonify({'success': True, 'message': 'Thread reset.'})

if __name__ == '__main__':
    app.run(port=5000, debug=True)
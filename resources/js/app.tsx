import React from 'react';
import { createRoot } from 'react-dom/client';
import PromptInputBox from './components/PromptInputBox';
import '../css/app.css';

// Declare global window interface for CSRF token and callbacks
declare global {
  interface Window {
    csrfToken: string;
    handleChatSend?: (message: string, files?: File[]) => void;
    setPromptLoading?: (loading: boolean) => void;
    promptInputRef?: React.RefObject<HTMLDivElement>;
  }
}

// Chat Input Wrapper Component
interface ChatInputWrapperProps {
  csrfToken: string;
  apiEndpoint: string;
  placeholder?: string;
}

const ChatInputWrapper: React.FC<ChatInputWrapperProps> = ({
  csrfToken,
  apiEndpoint,
  placeholder = "Message Mindory..."
}) => {
  const [isLoading, setIsLoading] = React.useState(false);
  const promptRef = React.useRef<HTMLDivElement>(null);

  // Expose loading setter to window for external control
  React.useEffect(() => {
    window.setPromptLoading = setIsLoading;
    window.promptInputRef = promptRef;
    return () => {
      delete window.setPromptLoading;
      delete window.promptInputRef;
    };
  }, []);

  const handleSend = async (message: string, files?: File[]) => {
    // If there's an external handler, use it
    if (window.handleChatSend) {
      window.handleChatSend(message, files);
      return;
    }

    // Default behavior: submit via form
    setIsLoading(true);

    try {
      const formData = new FormData();
      formData.append('question', message);
      formData.append('_token', csrfToken);

      if (files && files.length > 0) {
        formData.append('image', files[0]);
      }

      // Get selected AI model if available
      const modelInput = document.getElementById('selectedAiModel') as HTMLInputElement;
      if (modelInput?.value) {
        formData.append('model_id', modelInput.value);
      }

      // Dispatch custom event for the existing chat system to handle
      const event = new CustomEvent('promptInputSubmit', {
        detail: { message, files, formData }
      });
      document.dispatchEvent(event);

    } catch (error) {
      console.error('Error sending message:', error);
      setIsLoading(false);
    }
  };

  return (
    <PromptInputBox
      ref={promptRef}
      onSend={handleSend}
      isLoading={isLoading}
      placeholder={placeholder}
    />
  );
};

// Mount the React component
function mountPromptInput() {
  console.log('mountPromptInput called');
  const container = document.getElementById('react-prompt-input');
  if (!container) {
    console.warn('React prompt input container not found');
    return;
  }
  console.log('Container found:', container);

  const csrfToken = container.dataset.csrf ||
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ||
    '';
  const apiEndpoint = container.dataset.endpoint || '/api/chat/stream';
  const placeholder = container.dataset.placeholder || 'Message Mindory...';

  console.log('Creating React root...');
  try {
    const root = createRoot(container);
    console.log('Root created, rendering...');
    root.render(
      <React.StrictMode>
        <ChatInputWrapper
          csrfToken={csrfToken}
          apiEndpoint={apiEndpoint}
          placeholder={placeholder}
        />
      </React.StrictMode>
    );
    console.log('Render complete');
  } catch (error) {
    console.error('Error mounting React:', error);
    container.innerHTML = '<div style="padding: 16px; color: red;">Error loading component. Check console.</div>';
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountPromptInput);
} else {
  mountPromptInput();
}

export { PromptInputBox, ChatInputWrapper };

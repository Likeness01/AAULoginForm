"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const supportForm = document.getElementById("supportForm");
  const submitButton = document.getElementById("submitButton");

  // Add event listener for form submission
  supportForm.addEventListener("submit", function (event) {
    event.preventDefault(); // Prevent default form submission

    // Perform client-side validation before submitting
    if (validateForm()) {
      const formData = {
        supportType: supportForm.supportType.value,
        name: supportForm.name.value,
        email: supportForm.email.value,
        subject: supportForm.subject.value,
        message: supportForm.message.value,
        files: Array.from(fileInput.files),
      };

      toggleButtonLoadingState(true); // Enable loading state on submit

      submitForm(formData)
        .then(() => {
          toggleButtonLoadingState(false); // Disable loading state on successful submission
        })
        .catch(() => {
          toggleButtonLoadingState(false); // Disable loading state on submission failure
        });
    } else {
      console.log("Form validation failed.");
      console.log(validateForm());
    }
  });
});

// Function to toggle loading state of the button
function toggleButtonLoadingState(isLoading) {
  if (isLoading) {
    submitButton.textContent = submitButton.dataset.loadingText;
    submitButton.disabled = true;
  } else {
    submitButton.textContent = "Submit";
    submitButton.disabled = false;
  }
}

// Function to validate the entire form
function validateForm() {
  let isValid = true;

  // Validate support type
  const supportTypeInput = document.getElementById("supportType");
  if (supportTypeInput.value.trim() === "") {
    document.getElementById("supportTypeRequired").classList.add("invalid");
    isValid = false;
  } else {
    document.getElementById("supportTypeRequired").classList.remove("invalid");
  }

  // Validate name
  const nameInput = document.getElementById("name");
  if (nameInput.value.length < 4) {
    document.getElementById("nameLength").classList.add("invalid");
    isValid = false;
  } else {
    document.getElementById("nameLength").classList.remove("invalid");
  }

  // Validate email
  const emailInput = document.getElementById("email");
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
    document.getElementById("emailFormat").classList.add("invalid");
    isValid = false;
  } else {
    document.getElementById("emailFormat").classList.remove("invalid");
  }

  // Validate subject
  const subjectInput = document.getElementById("subject");
  if (subjectInput.value.length < 5) {
    document.getElementById("subjectLength").classList.add("invalid");
    isValid = false;
  } else {
    document.getElementById("subjectLength").classList.remove("invalid");
  }

  // Validate message
  const messageInput = document.getElementById("message");
  if (messageInput.value.length < 10) {
    document.getElementById("messageLength").classList.add("invalid");
    isValid = false;
  } else {
    document.getElementById("messageLength").classList.remove("invalid");
  }

  // Validate file input
  const fileInput = document.getElementById("file");
  const fileMessages = document
    .getElementById("fileMessage")
    .querySelectorAll("p");

  if (fileInput.files.length == 0 || fileInput.files.length > 4) {
    fileMessages[0].classList.add("invalid");
    isValid = false;
  } else {
    fileMessages[0].classList.remove("invalid");
  }

  const validFileTypes = ["image/jpeg", "image/png", "image/jpg"];
  const validSize = 2 * 1024 * 1024; // 2MB

  if (
    !Array.from(fileInput.files).every((file) =>
      validFileTypes.includes(file.type)
    )
  ) {
    fileMessages[1].classList.add("invalid");
    isValid = false;
  } else {
    fileMessages[1].classList.remove("invalid");
  }

  if (!Array.from(fileInput.files).every((file) => file.size <= validSize)) {
    fileMessages[2].classList.add("invalid");
    isValid = false;
  } else {
    fileMessages[2].classList.remove("invalid");
  }

  return isValid;
}

// Remaining functions like uploadFile, previewImages, removeImage, addValidation are as previously defined

// function to handle form submission

async function uploadFile(file) {
  const formData = new FormData();
  formData.append("file", file);

  try {
    console.log("Uploading file:", file.name);
    const response = await fetch("upload.php", {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      throw new Error("Failed to upload file.");
    }

    const data = await response.json();
    if (data.success) {
      console.log("File uploaded successfully:", data.filePath);
    } else {
      console.error("Upload error:", data.error);
    }
  } catch (error) {
    console.error("Upload error:", error);
  }
}

async function submitForm(formData) {
  try {
    console.log("Submitting form:", formData);
    const response = await fetch("submit_support_request.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(formData),
    });

    if (!response.ok) {
      throw new Error("Failed to submit form.");
    }

    const data = await response.json();
    if (data.success) {
      console.log("Form submitted successfully.");
    } else {
      console.error("Form submission error:", data.error);
    }
  } catch (error) {
    console.error("Form submission error:", error);
  }
}

// Function to add validation to input fields
function addValidation(inputId, messageId, validations) {
  const input = document.getElementById(inputId);
  const message = document.getElementById(messageId);

  input.addEventListener("input", function () {
    validateInput(input, message, validations);
  });
}

// Function to validate input
function validateInput(input, message, validations) {
  let isValid = true;
  validations.forEach((validation) => {
    const { condition, elementId, validClass, invalidClass } = validation;
    const element = document.getElementById(elementId);
    if (!condition(input)) {
      element.classList.remove(validClass);
      element.classList.add(invalidClass);
      isValid = false;
    } else {
      element.classList.remove(invalidClass);
      element.classList.add(validClass);
    }
  });

  if (isValid) {
    message.style.display = "none";
  } else {
    message.style.display = "block";
  }
}

// Function to preview images
function previewImages(input, previewContainerId) {
  const previewContainer = document.getElementById(previewContainerId);
  previewContainer.innerHTML = ""; // Clear existing previews

  Array.from(input.files).forEach((file, index) => {
    const reader = new FileReader();
    reader.onload = function (e) {
      console.log(`Previewing image ${index + 1}:`, file.name);
      const imgContainer = document.createElement("div");
      imgContainer.style.position = "relative";

      const img = document.createElement("img");
      img.src = e.target.result;
      imgContainer.appendChild(img);

      const removeBtn = document.createElement("span");
      removeBtn.textContent = "🗑";
      removeBtn.classList.add("remove-btn");
      removeBtn.dataset.index = index;
      imgContainer.appendChild(removeBtn);

      previewContainer.appendChild(imgContainer);
    };
    reader.readAsDataURL(file);
  });
}

// Remove image function
function removeImage(index) {
  const fileInput = document.getElementById("file");
  const dt = new DataTransfer();
  const files = Array.from(fileInput.files);

  files.forEach((file, i) => {
    if (i !== index) {
      dt.items.add(file);
    }
  });

  fileInput.files = dt.files;
  console.log(`Image removed at index ${index}`);
  previewImages(fileInput, "previewContainer");
}

// File field validations and preview
const fileInput = document.getElementById("file");
fileInput.addEventListener("change", function () {
  console.log("File input changed");
  previewImages(fileInput, "previewContainer");
});

document
  .getElementById("previewContainer")
  .addEventListener("click", function (e) {
    if (e.target.classList.contains("remove-btn")) {
      const index = e.target.dataset.index;
      console.log(`Remove button clicked for image at index ${index}`);
      removeImage(parseInt(index));
    }
  });

// Support Type field validations
addValidation("supportType", "supportTypeMessage", [
  {
    condition: (input) => input.value.trim() !== "", // Trim input to avoid whitespace validation bypass
    elementId: "supportTypeRequired",
    validClass: "valid",
    invalidClass: "invalid",
  },
]);

// Common Fields validations
addValidation("name", "nameMessage", [
  {
    condition: (input) => input.value.length >= 4,
    elementId: "nameLength",
    validClass: "valid",
    invalidClass: "invalid",
  },
]);

addValidation("email", "emailMessage", [
  {
    condition: (input) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value),
    elementId: "emailFormat",
    validClass: "valid",
    invalidClass: "invalid",
  },
]);

addValidation("subject", "subjectMessage", [
  {
    condition: (input) => input.value.length >= 5,
    elementId: "subjectLength",
    validClass: "valid",
    invalidClass: "invalid",
  },
]);

addValidation("message", "textMessage", [
  {
    condition: (input) => input.value.length >= 10,
    elementId: "messageLength",
    validClass: "valid",
    invalidClass: "invalid",
  },
]);

addValidation("file", "fileMessage", [
  {
    condition: (input) => input.files.length == 0 && input.files.length <= 4,
    elementId: "fileNumber",
    validClass: "valid",
    invalidClass: "invalid",
  },
  {
    condition: (input) =>
      Array.from(input.files).every((file) =>
        ["image/jpeg", "image/png", "image/jpg"].includes(file.type)
      ),
    elementId: "fileType",
    validClass: "valid",
    invalidClass: "invalid",
  },
  {
    condition: (input) =>
      Array.from(input.files).every((file) => file.size <= 2 * 1024 * 1024), // 2MB
    elementId: "fileSize",
    validClass: "valid",
    invalidClass: "invalid",
  },
]);

// Example usage for file upload (assuming fileInput is already defined)
fileInput.addEventListener("change", function () {
  console.log("File input change event triggered");
  Array.from(fileInput.files).forEach((file) => {
    uploadFile(file);
  });
});

// Example usage for form submission (adjust data structure to match your form fields)
const formData = {
  name: "John Doe",
  email: "john.doe@example.com",
  subject: "Example Subject",
  message: "Example message content",
};
console.log("Initial form data:", formData);

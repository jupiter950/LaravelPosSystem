@extends('layouts.app')
@section('title', __('purchase.purchases'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>Your Invoice</h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        <div class="p-8">
            

            <div class="py-8">
                <p class="py-4 font-bold text-[20px]">Please import your invoice</p>
                <div 
                    id="file-upload-container" 
                    class="relative border-2 border-gray-900 border-dashed rounded-md p-16 hover:bg-gray-200 hover:cursor-pointer"
                >
                    <input id="file-input" type="file" name="invoice" class="absolute inset-0 w-full h-full opacity-0" />
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <p id="upload-status" class="mt-2 font-bold text-gray-600">Drag and drop your files here or click to
                            browse</p>
                    </div>
                    <div id="progress-bar-container" class="mt-2 mb-4 hidden">
                        <div id="progress-bar" class="h-2 bg-blue-500 rounded-md"></div>
                    </div>
                    <div id="progress-info" class="flex justify-between hidden">
                        <div id="progress-percentage" class=" text-gray-500">0%</div>
                        <div id="file-count" class=" text-gray-500">0/0</div>
                    </div>
                    
                </div>
                <div id="button-container" class="flex justify-end hidden mt-4">
                    <button id="cancel-button"
                        class="px-4 py-2  bg-red-500 text-white rounded-md">Cancel</button>
                    <button id="confirm-button"
                        class="px-4 py-2 ml-2  bg-green-500 text-white rounded-md">Confirm</button>
                </div>

                <div id="spinner" class="hidden">
                    <div class="relative flex justify-center items-center hidden">
                        <div class="absolute animate-spin rounded-full h-32 w-32 border-t-4 border-b-4 border-blue-500"></div>
                        <img src="https://www.svgrepo.com/show/509001/avatar-thinking-9.svg" class="rounded-full h-28 w-28">
                    </div>
                </div>

            </div>

            <div class="py-8">
                <p class="py-4 font-bold text-[20px]">Imported Invoice</p>

                <div id="processed-data" class="py-4">
                    <p class="py-8 font-bold text-[16px] text-center">
                        No processed Data
                    </p>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>


    <script>
        const fileInput = document.getElementById('file-input');
        const progressBarContainer = document.getElementById('progress-bar-container');
        const progressInfo = document.getElementById('progress-info');
        const progressBar = document.getElementById('progress-bar');
        const progressPercentage = document.getElementById('progress-percentage');
        const fileCount = document.getElementById('file-count');
        const uploadStatus = document.getElementById('upload-status');
        const cancelButton = document.getElementById('cancel-button');
        const confirmButton = document.getElementById('confirm-button');
        const buttonContainer = document.getElementById('button-container');
        const processedInvoiceContainer = document.getElementById('processed-data');
        const detectedProductsContainer = document.getElementById('detected-data');
        const formData = new FormData();
    
        let progressUpdater;
        let uploadedFiles = 0;
        let totalFiles = 0;
    
        fileInput.addEventListener('change', handleFileUpload);
        cancelButton.addEventListener('click', cancelUpload);
        confirmButton.addEventListener('click', confirmUpload);
    
        function handleFileUpload(event) {
            const files = event.target.files;
            totalFiles = files.length;
            uploadedFiles = 0;
    
            // Show progress bar, file count, and buttons
            progressBarContainer.classList.remove('hidden');
            progressInfo.classList.remove('hidden');
            buttonContainer.classList.add('hidden');
            uploadStatus.textContent = 'Uploading...';
    
            // Reset progress bar
            progressBar.style.width = '0%';
    
            // Update file count
            fileCount.textContent = `0/${totalFiles}`;
    
            // Simulate file upload progress
            progressUpdater = setInterval(() => {
                // Simulate progress increase
                uploadedFiles++;
                const progress = (uploadedFiles / totalFiles) * 100;
    
                // Update progress bar
                progressBar.style.width = `${progress}%`;
                progressPercentage.textContent = `${Math.round(progress)}%`;
                fileCount.textContent = `${uploadedFiles}/${totalFiles}`;


                // Show the spinner
                document.getElementById('spinner').classList.remove('hidden');
    
                // Finish uploading
                if (uploadedFiles === totalFiles) {
                    clearInterval(progressUpdater);
                    uploadStatus.textContent = 'Uploading Completed!';
                    buttonContainer.classList.remove('hidden');
                    formData.append('invoice', fileInput.files[0]);
                }
            }, 500); // Adjust the interval to control the speed of the progress bar update
        }
    
        function cancelUpload() {
            clearInterval(progressUpdater);
            progressBar.style.width = '0%';
            progressPercentage.textContent = '0%';
            fileCount.textContent = '0/0';
            uploadedFiles = 0;
            totalFiles = 0;
            uploadStatus.textContent = 'Upload Cancelled';
            buttonContainer.classList.add('hidden');
        }
    
        function confirmUpload() {
            // Perform actions to send the uploaded files
            // Add your own logic here
            axios.post('{{ route('process_invoice') }}', formData, {
                headers: {
                    'Content-Type' : 'multipart/form-data'
                }
            })
            .then((res) => {
                console.log(res.data);
                // Hide the spinner
                document.getElementById('spinner').classList.add('hidden');
                
                processedInvoiceContainer.innerHTML = res.data;

                uploadStatus.innerHTML="<p class='text-center py-2 text-[18px]'>Processed your invoice</p>";

            })
            .catch(err => {
                console.log(err);

                // Hide the spinner
                document.getElementById('spinner').classList.add('hidden');
            })

    
            // Reset the UI
            clearInterval(progressUpdater);
            progressBar.style.width = '0%';
            progressPercentage.textContent = '0%';
            fileCount.textContent = '0/0';
            uploadedFiles = 0;
            totalFiles = 0;
            uploadStatus.innerHTML = "<div class='relative flex justify-center items-center'><div class='absolute animate-spin rounded-full h-32 w-32 border-t-4 border-b-4 border-blue-500'></div><img src='https://www.svgrepo.com/show/509001/avatar-thinking-9.svg' class='rounded-full h-28 w-28'></div> <div class='text-center py-2 font-bold'><p>Processing invoice...</p></div>"
            buttonContainer.classList.add('hidden');
            
            
        }
    </script>

@stop
